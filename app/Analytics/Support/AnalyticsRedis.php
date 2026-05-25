<?php

namespace App\Analytics\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AnalyticsRedis
{
    private static ?bool $available = null;

    private static ?int $checkedAt = null;

    public static function available(): bool
    {
        if (!config('analytics.use_redis', false)) {
            return false;
        }

        if (self::$available !== null && self::$checkedAt !== null && (time() - self::$checkedAt) < 120) {
            return self::$available;
        }

        try {
            $start = microtime(true);
            Redis::connection()->ping();
            $elapsed = microtime(true) - $start;
            self::$available = $elapsed < 2.0;
            if (!self::$available) {
                Log::warning('Analytics Redis ping too slow (' . round($elapsed, 2) . 's); using cache fallback.');
            }
        } catch (\Throwable $e) {
            Log::debug('Analytics Redis unavailable: ' . $e->getMessage());
            self::$available = false;
        }

        self::$checkedAt = time();

        return self::$available;
    }

    public static function resetCache(): void
    {
        self::$available = null;
        self::$checkedAt = null;
    }
}
