<?php

namespace App\Analytics\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AnalyticsRedis
{
    public static function available(): bool
    {
        try {
            Redis::connection()->ping();

            return true;
        } catch (\Throwable $e) {
            Log::debug('Analytics Redis unavailable: ' . $e->getMessage());

            return false;
        }
    }
}
