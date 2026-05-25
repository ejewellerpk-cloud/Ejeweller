<?php

namespace App\Analytics\Services;

use App\Analytics\Support\AnalyticsRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AnalyticsRealtimeService
{
    private function prefix(int $siteId): string
    {
        return config('analytics.realtime.redis_prefix', 'analytics:rt') . ':' . $siteId;
    }

    private function cacheKey(int $siteId, string $suffix): string
    {
        return 'analytics:rt:cache:' . $siteId . ':' . $suffix;
    }

    public function touchActiveVisitor(int $siteId, string $sessionUuid): void
    {
        if (!AnalyticsRedis::available()) {
            $key = $this->cacheKey($siteId, 'active');
            $active = Cache::get($key, []);
            $active[$sessionUuid] = now()->timestamp;
            Cache::put($key, $active, now()->addMinutes(10));

            return;
        }

        $key = $this->prefix($siteId) . ':active';
        $window = config('analytics.realtime.active_window_seconds', 300);
        Redis::zadd($key, now()->timestamp, $sessionUuid);
        Redis::expire($key, $window + 60);
        Redis::zremrangebyscore($key, 0, now()->subSeconds($window)->timestamp);
    }

    public function increment(int $siteId, string $metric, int $by = 1): void
    {
        if (!AnalyticsRedis::available()) {
            $key = $this->cacheKey($siteId, 'counters');
            $counters = Cache::get($key, []);
            $counters[$metric] = (int) ($counters[$metric] ?? 0) + $by;
            Cache::put($key, $counters, now()->addDay());

            return;
        }

        $key = $this->prefix($siteId) . ':counters';
        Redis::hincrby($key, $metric, $by);
        Redis::expire($key, 86400);
    }

    public function recordPage(int $siteId, string $path): void
    {
        $path = mb_substr($path, 0, 512);

        if (!AnalyticsRedis::available()) {
            $key = $this->cacheKey($siteId, 'pages');
            $pages = Cache::get($key, []);
            $pages[$path] = (int) ($pages[$path] ?? 0) + 1;
            Cache::put($key, $pages, now()->addDay());

            return;
        }

        $key = $this->prefix($siteId) . ':pages';
        Redis::zincrby($key, 1, $path);
        Redis::expire($key, 86400);
    }

    public function recordSource(int $siteId, string $source): void
    {
        if (!AnalyticsRedis::available()) {
            $key = $this->cacheKey($siteId, 'sources');
            $sources = Cache::get($key, []);
            $sources[$source] = (int) ($sources[$source] ?? 0) + 1;
            Cache::put($key, $sources, now()->addDay());

            return;
        }

        $key = $this->prefix($siteId) . ':sources';
        Redis::zincrby($key, 1, $source);
        Redis::expire($key, 86400);
    }

    public function snapshot(int $siteId): array
    {
        if (!AnalyticsRedis::available()) {
            return $this->snapshotFromCache($siteId);
        }

        $prefix = $this->prefix($siteId);
        $window = config('analytics.realtime.active_window_seconds', 300);
        $activeKey = $prefix . ':active';
        Redis::zremrangebyscore($activeKey, 0, now()->subSeconds($window)->timestamp);
        $activeVisitors = (int) Redis::zcard($activeKey);

        $counters = Redis::hgetall($prefix . ':counters') ?: [];
        $pages = $this->zsetTop($prefix . ':pages', 5);
        $sources = $this->zsetTop($prefix . ':sources', 5);

        $data = [
            'active_visitors' => $activeVisitors,
            'page_views_today' => (int) ($counters['page_views'] ?? 0),
            'orders_today' => (int) ($counters['orders'] ?? 0),
            'add_to_carts_today' => (int) ($counters['add_to_cart'] ?? 0),
            'top_pages' => $pages,
            'top_sources' => $sources,
            'updated_at' => now()->toIso8601String(),
        ];

        $this->cacheSnapshot($siteId, $data);

        return $data;
    }

    private function snapshotFromCache(int $siteId): array
    {
        $window = config('analytics.realtime.active_window_seconds', 300);
        $cutoff = now()->subSeconds($window)->timestamp;
        $active = Cache::get($this->cacheKey($siteId, 'active'), []);
        $activeVisitors = collect($active)->filter(fn ($ts) => $ts >= $cutoff)->count();

        $counters = Cache::get($this->cacheKey($siteId, 'counters'), []);
        $pages = $this->topFromAssoc(Cache::get($this->cacheKey($siteId, 'pages'), []), 5);
        $sources = $this->topFromAssoc(Cache::get($this->cacheKey($siteId, 'sources'), []), 5);

        return [
            'active_visitors' => $activeVisitors,
            'page_views_today' => (int) ($counters['page_views'] ?? 0),
            'orders_today' => (int) ($counters['orders'] ?? 0),
            'add_to_carts_today' => (int) ($counters['add_to_cart'] ?? 0),
            'top_pages' => $pages,
            'top_sources' => $sources,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function topFromAssoc(array $data, int $limit): array
    {
        arsort($data);

        return collect($data)->take($limit)->map(fn ($score, $label) => [
            'label' => $label,
            'value' => (int) $score,
        ])->values()->all();
    }

    private function zsetTop(string $key, int $limit): array
    {
        $raw = Redis::zrevrange($key, 0, $limit - 1, true) ?: [];

        return collect($raw)->map(fn ($score, $member) => [
            'label' => $member,
            'value' => (int) $score,
        ])->values()->all();
    }

    public function cacheSnapshot(int $siteId, array $data): void
    {
        Cache::put('analytics:realtime:' . $siteId, $data, 30);
    }
}
