<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        // Get ALL files from public storage recursively
        $allFiles = Storage::disk('public')->allFiles();
        
        // Filter only images and EXCLUDE 'conversions' folders
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $files = array_filter($allFiles, function($file) use ($imageExtensions) {
            $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $imageExtensions);
            $isConversion = str_contains($file, 'conversions/');
            return $isImage && !$isConversion;
        });

        // Sort files by modified time descending (newest first)
        usort($files, function($a, $b) {
            return Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a);
        });

        $items = [];
        foreach ($files as $file) {
            $filename = basename($file);
            // Skip hidden files
            if (str_starts_with($filename, '.')) continue;

            $items[] = [
                'id' => $file, // Use full path as ID for deletion
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
                'page' => (int)($request->page ?? 1),
                'limit' => 100,
                'totalPages' => 1
            ]
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
            $filename = time() . '-' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Store file in 'media' directory on 'public' disk
            $path = $file->storeAs('media', $filename, 'public');
            
            $uploadedFiles[] = [
                'id' => $filename,
                'url' => Storage::disk('public')->url($path),
                'filename' => $filename,
                'originalName' => $file->getClientOriginalName(),
                'mimetype' => $file->getClientMimeType(),
                'size' => Storage::disk('public')->size($path),
            ];
        }

        return response()->json($uploadedFiles, 201);
    }

    public function destroy($id)
    {
        // $id now contains the full path (e.g. 'media/filename.jpg' or '1/english.png')
        if (Storage::disk('public')->exists($id)) {
            Storage::disk('public')->delete($id);
            return response()->json(['message' => 'Media deleted successfully']);
        }
        return response()->json(['error' => 'File not found'], 404);
    }
}
