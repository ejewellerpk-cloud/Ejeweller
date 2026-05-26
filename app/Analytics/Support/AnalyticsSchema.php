<?php

namespace App\Analytics\Support;

use Illuminate\Support\Facades\Schema;

class AnalyticsSchema
{
    public static function hasEventsTable(): bool
    {
        try {
            return Schema::hasTable('analytics_events');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function isInstalled(): bool
    {
        try {
            return Schema::hasTable('analytics_sites')
                && Schema::hasTable('analytics_events');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
