<?php

namespace App\Listeners;

use App\Services\WebpImageService;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class ConvertUploadedMediaToWebp
{
    public function __construct(
        private readonly WebpImageService $webpImageService
    ) {}

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        try {
            $this->webpImageService->convertMediaToWebp($event->media);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('WebP upload listener failed.', [
                'media_id' => $event->media->id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
