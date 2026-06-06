<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WebpImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function __construct(
        private readonly WebpImageService $webpImageService
    ) {}

    public function index(Request $request)
    {
        $allFiles = Storage::disk('public')->allFiles();

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $files = array_filter($allFiles, function ($file) use ($imageExtensions) {
            $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $imageExtensions);
            $isConversion = str_contains($file, 'conversions/');

            return $isImage && !$isConversion;
        });

        usort($files, function ($a, $b) {
            return Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a);
        });

        $items = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (str_starts_with($filename, '.')) {
                continue;
            }

            $items[] = [
                'id' => $file,
                'url' => Storage::disk('public')->url($file),
                'originalName' => $filename,
                'filename' => $filename,
                'mimetype' => Storage::disk('public')->mimeType($file) ?: 'image/jpeg',
                'size' => Storage::disk('public')->size($file),
            ];
        }

        return response()->json([
            'items' => $items,
            'pagination' => [
                'total' => count($items),
                'page' => (int) ($request->page ?? 1),
                'limit' => 100,
                'totalPages' => 1,
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

        $uploadedFiles = [];
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $isSvg = $extension === 'svg';
            $isWebp = $extension === 'webp';

            if ($isSvg || $isWebp) {
                $filename = time() . '-' . Str::random(10) . '.' . $extension;
                $path = $file->storeAs('media', $filename, 'public');
                $mimetype = $isSvg ? 'image/svg+xml' : 'image/webp';
            } else {
                $tempPath = $file->getRealPath();
                $webpPath = $this->webpImageService->convertPathToWebp($tempPath);

                if (!$webpPath) {
                    continue;
                }

                $filename = time() . '-' . Str::random(10) . '.webp';
                $path = 'media/' . $filename;
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
                'originalName' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $extension,
                'mimetype' => $mimetype,
                'size' => Storage::disk('public')->size($path),
            ];
        }

        return response()->json($uploadedFiles, 201);
    }

    public function destroy($id)
    {
        if (Storage::disk('public')->exists($id)) {
            Storage::disk('public')->delete($id);

            return response()->json(['message' => 'Media deleted successfully']);
        }

        return response()->json(['error' => 'File not found'], 404);
    }
}
