<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\MetaCatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductToMetaCatalogJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 120;

    public function __construct(
        public int $productId,
        public bool $force = false,
    ) {
    }

    public function uniqueId(): string
    {
        return 'meta-catalog-product-' . $this->productId;
    }

    public function handle(MetaCatalogSyncService $sync): void
    {
        $product = Product::query()->find($this->productId);
        if (!$product) {
            return;
        }

        $record = $sync->detectChange($product);
        if (!$record) {
            return;
        }

        // Auto-sync only when forced or content became stale/pending/failed.
        if (!$this->force && !in_array($record->sync_status, [
            \App\Models\MetaCatalogProduct::STATUS_STALE,
            \App\Models\MetaCatalogProduct::STATUS_PENDING,
            \App\Models\MetaCatalogProduct::STATUS_FAILED,
        ], true)) {
            return;
        }

        $result = $sync->syncProduct($product, $this->force);
        if (!($result['ok'] ?? false) && empty($result['skipped'])) {
            Log::warning('meta_catalog_auto_sync_failed', [
                'product_id' => $this->productId,
                'message' => $result['message'] ?? null,
            ]);
        }
    }
}
