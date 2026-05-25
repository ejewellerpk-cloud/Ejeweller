<?php

namespace App\Analytics\Repositories;

use App\Analytics\Contracts\AnalyticsEventRepositoryInterface;
use App\Analytics\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;

class EloquentAnalyticsEventRepository implements AnalyticsEventRepositoryInterface
{
    public function insertBatch(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('analytics_events')->insert($chunk);
        }

        return count($rows);
    }

    public function existsByUuid(int $siteId, string $eventUuid): bool
    {
        return AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_uuid', $eventUuid)
            ->exists();
    }

    public function topEvents(int $siteId, string $from, string $to, int $limit = 10): array
    {
        return AnalyticsEvent::query()
            ->select('event_name', DB::raw('COUNT(*) as total'))
            ->where('site_id', $siteId)
            ->whereBetween('event_date', [$from, $to])
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function countByName(int $siteId, string $eventName, string $from, string $to): int
    {
        return AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', $eventName)
            ->whereBetween('event_date', [$from, $to])
            ->count();
    }

    public function topProducts(int $siteId, string $from, string $to, int $limit = 10): array
    {
        return AnalyticsEvent::query()
            ->select('product_id', DB::raw('COUNT(*) as views'))
            ->where('site_id', $siteId)
            ->where('event_name', 'product_viewed')
            ->whereNotNull('product_id')
            ->whereBetween('event_date', [$from, $to])
            ->groupBy('product_id')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function revenueSum(int $siteId, string $from, string $to): float
    {
        return (float) AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->whereIn('event_name', ['order_placed', 'order_confirmed'])
            ->whereBetween('event_date', [$from, $to])
            ->sum('revenue');
    }
}
