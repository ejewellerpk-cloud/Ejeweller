<?php

namespace App\Console\Commands;

use App\Services\WebpImageService;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ConvertExistingMediaToWebp extends Command
{
    protected $signature = 'media:convert-to-webp {--regenerate : Regenerate conversions after converting originals}';

    protected $description = 'Convert existing Spatie media originals to WebP and optionally regenerate conversions';

    public function handle(WebpImageService $webpImageService, FileManipulator $fileManipulator): int
    {
        $query = Media::query()
            ->where('mime_type', '!=', 'image/webp')
            ->where('mime_type', 'like', 'image/%');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No non-WebP image media found.');

            return self::SUCCESS;
        }

        $this->info("Converting {$total} media item(s) to WebP...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $converted = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(50, function ($mediaItems) use ($webpImageService, $fileManipulator, $bar, &$converted, &$skipped) {
            foreach ($mediaItems as $media) {
                if (!$webpImageService->shouldConvert($media)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if ($webpImageService->convertMediaToWebp($media)) {
                    $converted++;

                    if ($this->option('regenerate')) {
                        $media->refresh();
                        $fileManipulator->createDerivedFiles($media);
                    }
                } else {
                    $skipped++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Converted: {$converted}, Skipped: {$skipped}.");

        if ($converted > 0 && !$this->option('regenerate')) {
            $this->warn('Run with --regenerate to rebuild conversion files for converted media.');
        }

        return self::SUCCESS;
    }
}
