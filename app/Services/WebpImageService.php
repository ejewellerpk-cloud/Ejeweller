<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WebpImageService
{
    private const EXCLUDED_COLLECTIONS = [
        'product_video',
        'notification-file',
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

    public function convertMediaToWebp(Media $media, int $quality = 70): bool
    {
        if (!$this->shouldConvert($media)) {
            return false;
        }

        if ($media->getDiskDriverName() === 'local') {
            return $this->convertLocalMedia($media, $quality);
        }

        return $this->convertRemoteMedia($media, $quality);
    }

    public function convertPathToWebp(string $sourcePath, int $quality = 70): ?string
    {
        if (!is_file($sourcePath)) {
            return null;
        }

        if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) === 'webp') {
            return $sourcePath;
        }

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
