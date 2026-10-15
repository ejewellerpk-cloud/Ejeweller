<?php

namespace App\Console\Commands;

use App\Services\PostEx\PostExStatusSyncService;
use Illuminate\Console\Command;

class PostExSyncStatusesCommand extends Command
{
    protected $signature = 'postex:sync-statuses {--chunk=25 : Orders per API batch}';

    protected $description = 'Pull PostEx tracking statuses and sync shop order statuses';

    public function handle(PostExStatusSyncService $syncService): int
    {
        if (!$syncService->isAutoSyncEnabled()) {
            $this->info('PostEx auto status sync is disabled or PostEx is off.');

            return self::SUCCESS;
        }

        $chunk = max(1, min(50, (int) $this->option('chunk')));
        $stats = $syncService->sync($chunk);

        $this->info(sprintf(
            'PostEx sync done. checked=%d postex_updated=%d shop_updated=%d errors=%d',
            $stats['checked'],
            $stats['updated_postex'],
            $stats['updated_shop'],
            $stats['errors']
        ));

        return self::SUCCESS;
    }
}
