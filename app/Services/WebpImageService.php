<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
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

    private const MAX_SOURCE_BYTES = 15_728_640; // 15 MB — skip conversion above this

    private const MAX_DIMENSION = 2048;

    /** Decoded bitmap above this needs more RAM than typical 128M PHP limits allow. */
    private const MAX_PIXELS = 4_194_304; // 2048 × 2048

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
            return $this->withIncreasedMemoryLimit(function () use ($media, $quality) {
                if ($media->getDiskDriverName() === 'local') {
                    return $this->convertLocalMedia($media, $quality);
                }

                return $this->convertRemoteMedia($media, $quality);
            });
        } catch (Throwable $exception) {
            Log::warning('WebP conversion failed; keeping original image.', [
                'media_id' => $media->id,
                'collection' => $media->collection_name,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate and convert an upload before it enters the media library.
     * Returns a path to a WebP file (may be a new temp file) or null when unsafe.
     */
    public function prepareUploadFile(string $sourcePath, int $quality = 70): ?string
    {
        if (!is_file($sourcePath) || !$this->isSupported()) {
            return null;
        }

        if (!$this->canSafelyConvert($sourcePath)) {
            return null;
        }

        if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) === 'webp') {
            return $sourcePath;
        }

        return $this->convertPathToWebp($sourcePath, $quality);
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

        if (!$this->canSafelyConvert($sourcePath)) {
            return null;
        }

        try {
            return $this->withIncreasedMemoryLimit(function () use ($sourcePath, $quality) {
                $webpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);

                if ($webpPath === $sourcePath) {
                    $webpPath .= '.webp';
                }

                Image::load($sourcePath)
                    ->fit(Fit::Max, self::MAX_DIMENSION, self::MAX_DIMENSION)
                    ->quality($quality)
                    ->format('webp')
                    ->save($webpPath);

                if ($webpPath !== $sourcePath && is_file($sourcePath)) {
                    unlink($sourcePath);
                }

                return is_file($webpPath) ? $webpPath : null;
            });
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

        if (!$this->canSafelyConvert($tempSource)) {
            @unlink($tempSource);

            return false;
        }

        $converted = $this->convertPathToWebp($tempSource, $quality);
        @unlink($tempSource);

        if (!$converted || !is_file($converted)) {
            return false;
        }

        if ($converted !== $tempSource) {
            rename($converted, $tempWebp);
        }

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

    private function canSafelyConvert(string $sourcePath): bool
    {
        $size = @filesize($sourcePath);

        if ($size !== false && $size > self::MAX_SOURCE_BYTES) {
            Log::warning('WebP conversion skipped: source image too large.', [
                'source' => $sourcePath,
                'bytes' => $size,
            ]);

            return false;
        }

        $dimensions = @getimagesize($sourcePath);

        if ($dimensions === false) {
            Log::warning('WebP conversion skipped: unable to read image dimensions.', [
                'source' => $sourcePath,
            ]);

            return false;
        }

        [$width, $height] = $dimensions;

        if ($this->exceedsSafeDimensions($width, $height)) {
            Log::warning('WebP conversion skipped: image dimensions exceed safe memory limits.', [
                'source' => $sourcePath,
                'width' => $width,
                'height' => $height,
            ]);

            return false;
        }

        return true;
    }

    private function exceedsSafeDimensions(int $width, int $height): bool
    {
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            return true;
        }

        if ($width * $height > self::MAX_PIXELS) {
            return true;
        }

        return $this->estimatedDecodeBytes($width, $height) > $this->memoryBudgetBytes();
    }

    private function estimatedDecodeBytes(int $width, int $height): int
    {
        return $width * $height * 4;
    }

    private function memoryBudgetBytes(): int
    {
        $limit = $this->parseMemoryLimit((string) ini_get('memory_limit'));

        return (int) min($limit * 0.35, 48 * 1024 * 1024);
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function withIncreasedMemoryLimit(callable $callback): mixed
    {
        $current = (string) ini_get('memory_limit');
        $currentBytes = $this->parseMemoryLimit($current);
        $targetBytes = 256 * 1024 * 1024;

        if ($currentBytes > 0 && $currentBytes < $targetBytes) {
            @ini_set('memory_limit', '256M');
        }

        try {
            return $callback();
        } finally {
            if ($currentBytes > 0 && $currentBytes < $targetBytes) {
                @ini_set('memory_limit', $current);
            }
        }
    }

    private function parseMemoryLimit(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
