<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAssetService
{
    public const ROOT_FOLDERS = [
        'products' => 'Products',
        'categories' => 'Categories',
        'promotions' => 'Promotions',
        'sliders' => 'Sliders',
        'barcodes' => 'Barcodes',
        'miscellaneous' => 'Miscellaneous',
    ];

    public function buildFolderTree(): array
    {
        $disk = Storage::disk('public');
        $tree = [];

        foreach (self::ROOT_FOLDERS as $slug => $label) {
            $basePath = 'media/' . $slug;
            $node = [
                'id' => $basePath,
                'name' => $label,
                'children' => [],
            ];

            if (!$disk->exists($basePath)) {
                $tree[] = $node;
                continue;
            }

            foreach ($disk->directories($basePath) as $directory) {
                $childName = basename($directory);

                if ($slug === 'products') {
                    $childName = 'Product #' . $childName;
                } elseif ($slug === 'categories') {
                    if ($childName === 'brands') {
                        foreach ($disk->directories($directory) as $brandDir) {
                            $node['children'][] = [
                                'id' => $brandDir,
                                'name' => 'Brand #' . basename($brandDir),
                            ];
                        }
                        continue;
                    }

                    $childName = 'Category #' . $childName;
                } elseif ($slug === 'promotions') {
                    $childName = 'Promotion #' . $childName;
                } elseif ($slug === 'sliders') {
                    $childName = 'Slider #' . $childName;
                } elseif ($slug === 'barcodes') {
                    $childName = 'Barcode #' . $childName;
                }

                $node['children'][] = [
                    'id' => $directory,
                    'name' => $childName,
                ];
            }

            $tree[] = $node;
        }

        return $tree;
    }

    public function detachMediaRecord(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $relativePath = trim($media->getPath(), '/');

        foreach (['conversions', 'responsive-images'] as $subDirectory) {
            $directory = $relativePath . '/' . $subDirectory;
            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }
        }

        DB::table('media')->where('id', $media->id)->delete();
    }

    public function purgeSpatieRecordsForPath(string $path): void
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $directory = trim(str_replace('\\', '/', dirname($normalizedPath)), '.');
        $directory = $directory === '' ? '' : $directory . '/';
        $fileName = basename($normalizedPath);

        Media::query()
            ->where('disk', 'public')
            ->where('file_name', $fileName)
            ->get()
            ->filter(function (Media $media) use ($directory) {
                return str_starts_with(str_replace('\\', '/', $media->getPath()), $directory);
            })
            ->each(function (Media $media) {
                DB::table('media')->where('id', $media->id)->delete();
            });
    }

    public function deleteGalleryAsset(string $path): bool
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return false;
        }

        $this->purgeSpatieRecordsForPath($path);
        $disk->delete($path);

        $directory = trim(str_replace('\\', '/', dirname($path)), '.');
        if ($directory !== '') {
            foreach (['conversions', 'responsive-images'] as $subDirectory) {
                $subPath = $directory . '/' . $subDirectory;
                if ($disk->exists($subPath)) {
                    $disk->deleteDirectory($subPath);
                }
            }
        }

        return true;
    }

    public function normalizeFolder(?string $folder): string
    {
        $folder = trim(str_replace('\\', '/', (string) $folder), '/');

        if ($folder === '') {
            return 'media/miscellaneous';
        }

        if (!str_starts_with($folder, 'media/')) {
            $folder = 'media/' . $folder;
        }

        foreach (array_keys(self::ROOT_FOLDERS) as $allowedRoot) {
            if ($folder === 'media/' . $allowedRoot || str_starts_with($folder, 'media/' . $allowedRoot . '/')) {
                return $folder;
            }
        }

        return 'media/miscellaneous';
    }
}
