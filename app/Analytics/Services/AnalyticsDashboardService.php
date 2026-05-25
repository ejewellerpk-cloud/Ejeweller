<?php

namespace App\Analytics\Services;

use App\Analytics\Models\AnalyticsDailyMetric;
use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Models\AnalyticsSession;
use App\Analytics\Models\AnalyticsVisitor;
use App\Analytics\Repositories\EloquentAnalyticsEventRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardService
{
    public function __construct(
        private readonly AnalyticsRealtimeService $realtime,
        private readonly EloquentAnalyticsEventRepository $events,
    ) {}

    public function overview(int $siteId, string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $sessions = AnalyticsSession::query()
            ->where('site_id', $siteId)
            ->whereBetween('started_at', [$fromAt, $toAt]);

        $visitors = AnalyticsVisitor::query()
            ->where('site_id', $siteId)
            ->whereBetween('last_seen_at', [$fromAt, $toAt]);

        $pageViews = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'page_view')
            ->whereBetween('occurred_at', [$fromAt, $toAt])
            ->count();

        $orders = $this->events->countByName($siteId, 'order_placed', $from, $to);
        $revenue = $this->events->revenueSum($siteId, $from, $to);
        $bounces = (clone $sessions)->where('is_bounce', true)->count();
        $sessionCount = (clone $sessions)->count();

        return [
            'visitors' => $visitors->count(),
            'sessions' => $sessionCount,
            'page_views' => $pageViews,
            'orders' => $orders,
            'revenue' => $revenue,
            'bounce_rate' => $sessionCount > 0 ? round(($bounces / $sessionCount) * 100, 2) : 0,
            'conversion_rate' => $sessionCount > 0 ? round(($orders / $sessionCount) * 100, 2) : 0,
            'realtime' => $this->realtime->snapshot($siteId),
        ];
    }

    public function dailySeries(int $siteId, string $from, string $to): array
    {
        return AnalyticsDailyMetric::query()
            ->where('site_id', $siteId)
            ->whereBetween('metric_date', [$from, $to])
            ->orderBy('metric_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->metric_date->format('Y-m-d'),
                'visitors' => $row->visitors,
                'sessions' => $row->sessions,
                'page_views' => $row->page_views,
                'orders' => $row->orders,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    public function sources(int $siteId, string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        return AnalyticsSession::query()
            ->select('source', DB::raw('COUNT(*) as sessions'))
            ->where('site_id', $siteId)
            ->whereBetween('started_at', [$fromAt, $toAt])
            ->groupBy('source')
            ->orderByDesc('sessions')
            ->limit(15)
            ->get()
            ->toArray();
    }

    public function topProducts(int $siteId, string $from, string $to): array
    {
        return $this->events->topProducts($siteId, $from, $to);
    }

    public function funnel(int $siteId, string $from, string $to): array
    {
        $steps = [
            ['key' => 'page_view', 'label' => 'Homepage / Pages'],
            ['key' => 'category_viewed', 'label' => 'Category'],
            ['key' => 'product_viewed', 'label' => 'Product'],
            ['key' => 'add_to_cart', 'label' => 'Cart'],
            ['key' => 'checkout_started', 'label' => 'Checkout'],
            ['key' => 'order_placed', 'label' => 'Order'],
        ];

        $counts = [];
        foreach ($steps as $step) {
            $counts[] = [
                'step' => $step['label'],
                'count' => $this->events->countByName($siteId, $step['key'], $from, $to),
            ];
        }

        $base = max(1, $counts[0]['count'] ?? 1);
        foreach ($counts as &$row) {
            $row['conversion_pct'] = round(($row['count'] / $base) * 100, 2);
        }

        return $counts;
    }
}
