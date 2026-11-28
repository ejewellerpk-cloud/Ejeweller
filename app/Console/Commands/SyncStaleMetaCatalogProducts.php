<?php

namespace App\Console\Commands;

use App\Services\MetaCatalogSyncService;
use Illuminate\Console\Command;

class SyncStaleMetaCatalogProducts extends Command
{
    protected $signature = 'meta-catalog:sync-stale {--limit=50}';

    protected $description = 'Sync stale/pending Meta catalog products from the local database';

    public function handle(MetaCatalogSyncService $sync): int
    {
        $result = $sync->syncStaleOrChanged((int) $this->option('limit'));
        $this->info(sprintf(
            'Meta catalog sync — ok:%d failed:%d skipped:%d',
            $result['ok'] ?? 0,
            $result['failed'] ?? 0,
            $result['skipped'] ?? 0
        ));

        return self::SUCCESS;
    }
}
