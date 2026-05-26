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

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->index(
                ['site_id', 'product_id', 'event_date', 'event_name'],
                'analytics_events_site_product_date_name_idx'
            );
        });
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
