<?php

namespace App\Analytics\Services;

use App\Analytics\Support\AnalyticsRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AnalyticsIngestionBufferService
{
    public function isAvailable(): bool
    {
        return AnalyticsRedis::available();
    }

    public function push(int $siteId, array $payload): void
    {
        if ($this->isAvailable()) {
            try {
                Redis::rpush($this->bufferKey($siteId), json_encode($payload));

                return;
            } catch (\Throwable) {
                // Fall through to cache buffer
            }
        }

        $cacheKey = $this->cacheBufferKey($siteId);
        $queue = Cache::get($cacheKey, []);
        $queue[] = $payload;
        Cache::put($cacheKey, $queue, now()->addDay());
    }

    public function popBatch(int $siteId, int $limit = 100): array
    {
        if ($this->isAvailable()) {
            $key = $this->bufferKey($siteId);
            $items = [];

            for ($i = 0; $i < $limit; $i++) {
                $raw = Redis::lpop($key);
                if ($raw === null) {
                    break;
                }
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $items[] = $decoded;
                }
            }

            return $items;
        }

        $cacheKey = $this->cacheBufferKey($siteId);
        $queue = Cache::get($cacheKey, []);
        $items = array_splice($queue, 0, $limit);
        Cache::put($cacheKey, $queue, now()->addDay());

        return $items;
    }

    public function bufferLength(int $siteId): int
    {
        if ($this->isAvailable()) {
            return (int) Redis::llen($this->bufferKey($siteId));
        }

        return count(Cache::get($this->cacheBufferKey($siteId), []));
    }

    private function bufferKey(int $siteId): string
    {
        return config('analytics.ingest.redis_buffer_key', 'analytics:ingest:buffer') . ':' . $siteId;
    }

    private function cacheBufferKey(int $siteId): string
    {
        return 'analytics:ingest:cache-buffer:' . $siteId;
    }
}
