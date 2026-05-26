<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analytics_events')) {
            return;
        }

        $indexName = 'analytics_events_site_product_date_name_idx';
        $exists = collect(\Illuminate\Support\Facades\DB::select(
            'SHOW INDEX FROM analytics_events WHERE Key_name = ?',
            [$indexName]
        ))->isNotEmpty();

        if (!$exists) {
            Schema::table('analytics_events', function (Blueprint $table) use ($indexName) {
                $table->index(
                    ['site_id', 'product_id', 'event_date', 'event_name'],
                    $indexName
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('analytics_events')) {
            return;
        }

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('analytics_events_site_product_date_name_idx');
        });
    }
};
