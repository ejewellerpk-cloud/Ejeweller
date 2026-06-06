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
        $this->webpImageService->convertMediaToWebp($event->media);
    }
}
