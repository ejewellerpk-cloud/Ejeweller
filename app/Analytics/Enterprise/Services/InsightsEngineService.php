<?php

namespace App\Analytics\Enterprise\Services;

use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Support\AnalyticsSchema;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InsightsEngineService
{
    public function generateForSite(int $siteId, string $from, string $to): int
    {
        if (!AnalyticsSchema::hasTable('analytics_ai_insights')) {
            return 0;
        }

        $created = 0;
        $created += $this->detectConversionDrop($siteId, $from, $to);
        $created += $this->detectHighViewLowSales($siteId, $from, $to);
        $created += $this->detectCartAbandonmentSpike($siteId, $from, $to);

        return $created;
    }

    public function list(int $siteId, ?string $status = 'active'): array
    {
        if (!AnalyticsSchema::hasTable('analytics_ai_insights')) {
            return [];
        }

        $q = DB::table('analytics_ai_insights')->where('site_id', $siteId)->orderByDesc('detected_at');
        if ($status) {
            $q->where('status', $status);
        }

        return $q->limit(50)->get()->map(fn ($r) => [
            'id' => $r->id,
            'type' => $r->insight_type,
            'severity' => $r->severity,
            'title' => $r->title,
            'summary' => $r->summary,
            'recommendation' => $r->recommendation,
            'metrics' => json_decode($r->metrics ?? '{}', true),
            'detected_at' => $r->detected_at,
            'status' => $r->status,
        ])->all();
    }

    public function dismiss(int $siteId, int $insightId): bool
    {
        return (bool) DB::table('analytics_ai_insights')
            ->where('site_id', $siteId)
            ->where('id', $insightId)
            ->update(['status' => 'dismissed', 'dismissed_at' => now(), 'updated_at' => now()]);
    }

    private function storeInsight(int $siteId, string $type, string $severity, string $title, string $summary, string $recommendation, array $metrics): int
    {
        $exists = DB::table('analytics_ai_insights')
            ->where('site_id', $siteId)
            ->where('insight_type', $type)
            ->where('status', 'active')
            ->where('detected_at', '>=', now()->subDay())
            ->exists();

        if ($exists) {
            return 0;
        }

        DB::table('analytics_ai_insights')->insert([
            'site_id' => $siteId,
            'insight_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'summary' => $summary,
            'recommendation' => $recommendation,
            'metrics' => json_encode($metrics),
            'status' => 'active',
            'detected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return 1;
    }

    private function detectConversionDrop(int $siteId, string $from, string $to): int
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return 0;
        }

        $sessions = (int) DB::table('analytics_sessions')
            ->where('site_id', $siteId)
            ->whereBetween('started_at', [$from, $to])
            ->count();

        $orders = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'order_placed')
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        $rate = $sessions > 0 ? round(($orders / $sessions) * 100, 2) : 0;

        if ($sessions < 50 || $rate >= 1.5) {
            return 0;
        }

        return $this->storeInsight(
            $siteId,
            'conversion_drop',
            'warning',
            'Conversion rate is below benchmark',
            "Session conversion is {$rate}% across {$sessions} sessions in the selected period.",
            'Review checkout friction, shipping costs, and top exit pages in Funnels.',
            ['conversion_rate' => $rate, 'sessions' => $sessions, 'orders' => $orders]
        );
    }

    private function detectHighViewLowSales(int $siteId, string $from, string $to): int
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return 0;
        }

        $views = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'product_viewed')
            ->whereBetween('occurred_at', [$from, $to])
            ->select('product_id', DB::raw('COUNT(*) as views'))
            ->groupBy('product_id')
            ->having('views', '>=', 30)
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        foreach ($views as $row) {
            $carts = AnalyticsEvent::query()
                ->where('site_id', $siteId)
                ->where('event_name', 'add_to_cart')
                ->where('product_id', $row->product_id)
                ->whereBetween('occurred_at', [$from, $to])
                ->count();

            if ($carts < 3) {
                return $this->storeInsight(
                    $siteId,
                    'high_view_low_sales',
                    'info',
                    'High-traffic product with low cart adds',
                    "Product #{$row->product_id} has {$row->views} views but only {$carts} add-to-cart events.",
                    'Check pricing, images, variants, and reviews on this product page.',
                    ['product_id' => $row->product_id, 'views' => $row->views, 'add_to_cart' => $carts]
                );
            }
        }

        return 0;
    }

    private function detectCartAbandonmentSpike(int $siteId, string $from, string $to): int
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return 0;
        }

        $started = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'checkout_started')
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        $abandoned = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'checkout_abandoned')
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        if ($started < 10) {
            return 0;
        }

        $rate = round(($abandoned / max(1, $started)) * 100, 1);
        if ($rate < 40) {
            return 0;
        }

        return $this->storeInsight(
            $siteId,
            'cart_abandonment_spike',
            'critical',
            'Checkout abandonment is elevated',
            "{$rate}% of checkout sessions show abandonment signals ({$abandoned}/{$started}).",
            'Enable cart recovery campaigns and review payment methods on checkout.',
            ['abandon_rate' => $rate, 'checkout_started' => $started, 'checkout_abandoned' => $abandoned]
        );
    }
}
