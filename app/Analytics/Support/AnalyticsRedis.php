<?php

namespace App\Analytics\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AnalyticsRedis
{
    private static ?bool $cachedAvailability = null;

    private static ?int $lastUnavailableLogAt = null;

    public static function available(): bool
    {
        if (self::$cachedAvailability !== null) {
            return self::$cachedAvailability;
        }

        try {
            Redis::connection()->ping();
            self::$cachedAvailability = true;

            return true;
        } catch (\Throwable $e) {
            self::$cachedAvailability = false;
            self::logUnavailableOnce($e->getMessage());

            return false;
        }
    }

    public static function resetCache(): void
    {
        self::$cachedAvailability = null;
    }

    private static function logUnavailableOnce(string $message): void
    {
        $now = time();

        if (self::$lastUnavailableLogAt !== null && ($now - self::$lastUnavailableLogAt) < 300) {
            return;
        }

        self::$lastUnavailableLogAt = $now;
        Log::debug('Analytics Redis unavailable: ' . $message);
    }
}
