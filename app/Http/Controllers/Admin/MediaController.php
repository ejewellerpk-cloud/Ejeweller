<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaAssetService;
use App\Services\WebpImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(
        private readonly WebpImageService $webpImageService,
        private readonly MediaAssetService $mediaAssetService
    ) {}

    public function index(Request $request)
    {
        $folder = trim(str_replace('\\', '/', (string) $request->get('folder', 'all')));
        $search = strtolower(trim((string) $request->get('search', '')));
        $disk = Storage::disk('public');

        if ($folder === '' || $folder === 'all') {
            $files = $disk->allFiles();
        } elseif ($disk->exists($folder)) {
            $files = $disk->allFiles($folder);
        } else {
            $files = [];
        }

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $files = array_values(array_filter($files, function ($file) use ($imageExtensions) {
            $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $imageExtensions);
            $isConversion = str_contains($file, '/conversions/') || str_contains($file, '/responsive-images/');

            return $isImage && !$isConversion;
        }));

        if ($search !== '') {
            $files = array_values(array_filter($files, function ($file) use ($search) {
                return str_contains(strtolower(basename($file)), $search);
            }));
        }

        usort($files, function ($a, $b) use ($disk) {
            return $disk->lastModified($b) <=> $disk->lastModified($a);
        });

        $items = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (str_starts_with($filename, '.')) {
                continue;
            }

            $items[] = [
                'id' => $file,
                'url' => $disk->url($file),
                'originalName' => $filename,
                'filename' => $filename,
                'folder' => trim(str_replace('\\', '/', dirname($file)), '.'),
                'mimetype' => $disk->mimeType($file) ?: 'image/jpeg',
                'size' => $disk->size($file),
            ];
        }

        $limit = max(1, (int) ($request->get('limit', 100)));
        $page = max(1, (int) ($request->get('page', 1)));
        $total = count($items);
        $offset = ($page - 1) * $limit;
        $pagedItems = array_slice($items, $offset, $limit);

        return response()->json([
            'folders' => $this->mediaAssetService->buildFolderTree(),
            'currentFolder' => $folder === '' ? 'all' : $folder,
            'items' => $pagedItems,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => max(1, (int) ceil($total / $limit)),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $files = $request->file('files');

        if (!$files) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        $folder = $this->mediaAssetService->normalizeFolder($request->get('folder'));
        $uploadedFiles = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $isSvg = $extension === 'svg';
            $isWebp = $extension === 'webp';

            if ($isSvg || $isWebp) {
                $filename = time() . '-' . uniqid('', true) . '.' . $extension;
                $path = $file->storeAs($folder, $filename, 'public');
                $mimetype = $isSvg ? 'image/svg+xml' : 'image/webp';
            } else {
                $tempPath = $file->getRealPath();
                $webpPath = $this->webpImageService->prepareUploadFile($tempPath);

                if (!$webpPath) {
                    return response()->json([
                        'error' => 'Image exceeds safe size. Maximum 2048×2048 pixels and 2 MB.',
                    ], 422);
                }

                $filename = time() . '-' . uniqid('', true) . '.webp';
                $path = $folder . '/' . $filename;
                Storage::disk('public')->put($path, file_get_contents($webpPath));

                if ($webpPath !== $tempPath && is_file($webpPath)) {
                    @unlink($webpPath);
                }

                $mimetype = 'image/webp';
            }

            $uploadedFiles[] = [
                'id' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => basename($path),
                'folder' => $folder,
                'originalName' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $extension,
                'mimetype' => $mimetype,
                'size' => Storage::disk('public')->size($path),
            ];
        }

        return response()->json($uploadedFiles, 201);
    }

    public function storeFromUrl(Request $request)
    {
        $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'folder' => ['nullable', 'string', 'max:255'],
        ]);

        $url = $request->input('url');
        $folder = $this->mediaAssetService->normalizeFolder($request->get('folder'));

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Shopperzz/1.0)'])
                ->get($url);

            if (!$response->successful()) {
                return response()->json(['error' => 'Could not download image from URL'], 422);
            }

            $body = $response->body();
            if ($body === '' || strlen($body) > 2 * 1024 * 1024) {
                return response()->json(['error' => 'Image exceeds 2MB limit or is empty'], 422);
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($body) ?: '';
            if (!str_starts_with($mime, 'image/')) {
                return response()->json(['error' => 'URL must point to an image file'], 422);
            }

            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                default => 'jpg',
            };

            if ($extension === 'svg' || $extension === 'webp') {
                $filename = time() . '-' . uniqid('', true) . '.' . $extension;
                $path = $folder . '/' . $filename;
                Storage::disk('public')->put($path, $body);
                $mimetype = $extension === 'svg' ? 'image/svg+xml' : 'image/webp';
            } else {
                $tempFile = tempnam(sys_get_temp_dir(), 'img_url_');
                file_put_contents($tempFile, $body);
                $webpPath = $this->webpImageService->prepareUploadFile($tempFile);
                @unlink($tempFile);

                if (!$webpPath) {
                    return response()->json([
                        'error' => 'Image exceeds safe size. Maximum 2048×2048 pixels and 2 MB.',
                    ], 422);
                }

                $filename = time() . '-' . uniqid('', true) . '.webp';
                $path = $folder . '/' . $filename;
                Storage::disk('public')->put($path, file_get_contents($webpPath));

                if (is_file($webpPath)) {
                    @unlink($webpPath);
                }

                $mimetype = 'image/webp';
                $extension = 'webp';
            }

            $uploadedFile = [
                'id' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => basename($path),
                'folder' => $folder,
                'originalName' => pathinfo(parse_url($url, PHP_URL_PATH) ?: 'image', PATHINFO_FILENAME) . '.' . $extension,
                'mimetype' => $mimetype,
                'size' => Storage::disk('public')->size($path),
            ];

            return response()->json($uploadedFile, 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to import image from URL'], 422);
        }
    }

    public function destroy($id)
    {
        if (Storage::disk('public')->exists($id)) {
            Storage::disk('public')->delete($id);

            return response()->json(['message' => 'Media deleted successfully']);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deleted = 0;
        $failed = 0;

        foreach ($request->ids as $id) {
            if (Storage::disk('public')->exists($id)) {
                Storage::disk('public')->delete($id);
                $deleted++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'message' => 'Bulk delete completed',
            'deleted' => $deleted,
            'failed' => $failed,
        ]);
    }
}
