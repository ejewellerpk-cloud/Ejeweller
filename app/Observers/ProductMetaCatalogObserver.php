<?php

namespace App\Observers;

use App\Jobs\SyncProductToMetaCatalogJob;
use App\Models\MetaCatalogProduct;
use App\Models\Product;
use App\Services\MetaCatalogSyncService;
use Illuminate\Support\Facades\Schema;

class ProductMetaCatalogObserver
{
    public function saved(Product $product): void
    {
        if (!Schema::hasTable('meta_catalog_products')) {
            return;
        }

        $tracked = MetaCatalogProduct::query()->where('product_id', $product->id)->exists();
        if (!$tracked) {
            return;
        }

        try {
            app(MetaCatalogSyncService::class)->detectChange($product);
            SyncProductToMetaCatalogJob::dispatch($product->id, false)->afterCommit();
        } catch (\Throwable $e) {
            // Never break product save path.
            report($e);
        }
    }

    public function deleted(Product $product): void
    {
        // Soft deletes leave history; catalog availability will flip on next sync if re-saved.
    }
}
