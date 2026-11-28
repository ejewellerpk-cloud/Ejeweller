<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('marketing_campaign_product')) {
            Schema::create('marketing_campaign_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['marketing_campaign_id', 'product_id'], 'mcp_campaign_product_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaign_product');
    }
};
