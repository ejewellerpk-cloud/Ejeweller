<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaAssetService;
use App\Services\WebpImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(
        private readonly WebpImageService $webpImageService,
        private readonly MediaAssetService $mediaAssetService
    ) {}

    public function index(Request $request)
    {
        $folder = $this->mediaAssetService->normalizeFolder($request->get('folder'));
        $search = strtolower(trim((string) $request->get('search', '')));
        $disk = Storage::disk('public');

        $directories = [$folder];
        if (!$disk->exists($folder)) {
            $directories = [];
        }

        $files = [];
        foreach ($directories as $directory) {
            foreach ($disk->allFiles($directory) as $file) {
                $files[] = $file;
            }
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
            'currentFolder' => $folder,
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

    public function destroy($id)
    {
        if ($this->mediaAssetService->deleteGalleryAsset($id)) {
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
            if ($this->mediaAssetService->deleteGalleryAsset($id)) {
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
