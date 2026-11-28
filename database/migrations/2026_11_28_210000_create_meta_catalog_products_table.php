<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('meta_catalog_products')) {
            Schema::create('meta_catalog_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('retailer_id', 100);
                $table->string('meta_catalog_id', 64)->nullable();
                $table->string('meta_product_id', 64)->nullable();
                $table->string('sync_status', 32)->default('pending');
                $table->string('content_hash', 64)->nullable();
                $table->json('last_payload')->nullable();
                $table->json('validation_errors')->nullable();
                $table->json('validation_warnings')->nullable();
                $table->text('last_error')->nullable();
                $table->string('batch_handle', 191)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamp('last_validated_at')->nullable();
                $table->timestamps();

                $table->unique('product_id');
                $table->unique(['meta_catalog_id', 'retailer_id'], 'mcp_catalog_retailer_unique');
                $table->index(['sync_status', 'last_synced_at']);
            });
        }

        if (!Schema::hasTable('meta_catalog_sync_logs')) {
            Schema::create('meta_catalog_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('action', 32);
                $table->string('status', 16);
                $table->text('message')->nullable();
                $table->json('response')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_catalog_sync_logs');
        Schema::dropIfExists('meta_catalog_products');
    }
};
