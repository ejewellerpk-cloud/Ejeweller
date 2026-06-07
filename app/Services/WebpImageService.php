<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class WebpImageService
{
    private const EXCLUDED_COLLECTIONS = [
        'product_video',
        'notification-file',
        'product-barcode',
        'product-variation-barcode',
    ];

    private const EXCLUDED_MIMES = [
        'image/svg+xml',
        'image/webp',
        'image/gif',
    ];

    public function shouldConvert(Media $media): bool
    {
        if (in_array($media->collection_name, self::EXCLUDED_COLLECTIONS, true)) {
            return false;
        }

        $mimeType = $media->mime_type ?? '';

        if (in_array($mimeType, self::EXCLUDED_MIMES, true)) {
            return false;
        }

        return str_starts_with($mimeType, 'image/');
    }

    public function isSupported(): bool
    {
        if (function_exists('imagewebp')) {
            return true;
        }

        if (extension_loaded('imagick')) {
            $formats = \Imagick::queryFormats('WEBP');

            return !empty($formats);
        }

        return false;
    }

    public function convertMediaToWebp(Media $media, int $quality = 70): bool
    {
        if (!$this->shouldConvert($media)) {
            return false;
        }

        if (!$this->isSupported()) {
            Log::warning('WebP conversion skipped: server does not support WebP encoding.', [
                'media_id' => $media->id,
                'collection' => $media->collection_name,
            ]);

            return false;
        }

        try {
            if ($media->getDiskDriverName() === 'local') {
                return $this->convertLocalMedia($media, $quality);
            }

            return $this->convertRemoteMedia($media, $quality);
        } catch (Throwable $exception) {
            Log::warning('WebP conversion failed; keeping original image.', [
                'media_id' => $media->id,
                'collection' => $media->collection_name,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function convertPathToWebp(string $sourcePath, int $quality = 70): ?string
    {
        if (!is_file($sourcePath)) {
            return null;
        }

        if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) === 'webp') {
            return $sourcePath;
        }

        if (!$this->isSupported()) {
            return null;
        }

        try {
            $webpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);

            if ($webpPath === $sourcePath) {
                $webpPath .= '.webp';
            }

            Image::load($sourcePath)
                ->quality($quality)
                ->format('webp')
                ->save($webpPath);

            if ($webpPath !== $sourcePath && is_file($sourcePath)) {
                unlink($sourcePath);
            }

            return is_file($webpPath) ? $webpPath : null;
        } catch (Throwable $exception) {
            Log::warning('WebP path conversion failed.', [
                'source' => $sourcePath,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function convertLocalMedia(Media $media, int $quality): bool
    {
        $sourcePath = $media->getPath();

        if (!is_file($sourcePath)) {
            return false;
        }

        $webpPath = $this->convertPathToWebp($sourcePath, $quality);

        if (!$webpPath) {
            return false;
        }

        $this->updateMediaRecord($media, basename($webpPath), filesize($webpPath));

        return true;
    }

    private function convertRemoteMedia(Media $media, int $quality): bool
    {
        $disk = Storage::disk($media->disk);
        $relativePath = $media->getPathRelativeToRoot();

        if (!$disk->exists($relativePath)) {
            return false;
        }

        $tempSource = tempnam(sys_get_temp_dir(), 'webp_src_');
        $tempWebp = tempnam(sys_get_temp_dir(), 'webp_out_') . '.webp';

        file_put_contents($tempSource, $disk->get($relativePath));

        Image::load($tempSource)
            ->quality($quality)
            ->format('webp')
            ->save($tempWebp);

        @unlink($tempSource);

        if (!is_file($tempWebp)) {
            return false;
        }

        $newFileName = pathinfo($media->file_name, PATHINFO_FILENAME) . '.webp';
        $directory = dirname($relativePath);
        $newRelativePath = ($directory === '.' ? '' : $directory . '/') . $newFileName;

        $disk->put($newRelativePath, file_get_contents($tempWebp));
        $disk->delete($relativePath);
        @unlink($tempWebp);

        $this->updateMediaRecord($media, $newFileName, $disk->size($newRelativePath));

        return true;
    }

    private function updateMediaRecord(Media $media, string $newFileName, int $size): void
    {
        $media->file_name = $newFileName;
        $media->mime_type = 'image/webp';
        $media->size = $size;
        $media->save();
    }
}
