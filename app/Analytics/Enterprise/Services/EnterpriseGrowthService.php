<?php

namespace App\Analytics\Enterprise\Services;

use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Models\AnalyticsSession;
use App\Analytics\Services\AnalyticsAdvancedService;
use App\Analytics\Services\AnalyticsDashboardService;
use App\Analytics\Support\AnalyticsSchema;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnterpriseGrowthService
{
    public function __construct(
        private readonly AnalyticsDashboardService $dashboard,
        private readonly AnalyticsAdvancedService $advanced,
    ) {}

    public function recoveryOverview(int $siteId, string $from, string $to): array
    {
        $funnel = $this->dashboard->funnel($siteId, $from, $to);
        $byStep = collect($funnel)->keyBy('step');
        $carts = (int) ($byStep->get('Cart')['count'] ?? 0);
        $checkout = (int) ($byStep->get('Checkout')['count'] ?? 0);
        $orders = (int) ($byStep->get('Order')['count'] ?? 0);
        $abandoned = $this->countEvents($siteId, 'checkout_abandoned', $from, $to);
        $started = $this->countEvents($siteId, 'checkout_started', $from, $to);
        $abandonRate = $carts > 0 ? round((($carts - $orders) / max(1, $carts)) * 100, 1) : 0;
        $checkoutAbandonRate = $started > 0
            ? round(($abandoned / max(1, $started)) * 100, 1)
            : 0;

        $queue = ['pending' => 0, 'sent' => 0, 'recovered' => 0, 'failed' => 0];
        $campaigns = [];
        if (AnalyticsSchema::hasTable('analytics_recovery_queue')) {
            $queue = DB::table('analytics_recovery_queue')
                ->where('site_id', $siteId)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all() + $queue;
        }
        if (AnalyticsSchema::hasTable('analytics_recovery_campaigns')) {
            $campaigns = DB::table('analytics_recovery_campaigns')
                ->where('site_id', $siteId)
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'channel' => $c->channel,
                    'is_active' => (bool) $c->is_active,
                    'discount_percent' => $c->discount_percent,
                    'delays_minutes' => json_decode($c->delays_minutes ?? '[]', true),
                ])
                ->all();
        }

        return [
            'kpis' => [
                'add_to_cart' => $carts,
                'checkout_started' => $checkout ?: $started,
                'orders' => $orders,
                'abandoned' => $abandoned,
                'cart_abandonment_rate' => $abandonRate,
                'checkout_abandonment_rate' => $checkoutAbandonRate,
                'recoverable_value_estimate' => $this->estimateRecoverableValue($siteId, $from, $to),
            ],
            'queue' => [
                'pending' => (int) ($queue['pending'] ?? 0),
                'sent' => (int) ($queue['sent'] ?? 0),
                'recovered' => (int) ($queue['recovered'] ?? 0),
                'failed' => (int) ($queue['failed'] ?? 0),
            ],
            'campaigns' => $campaigns,
            'recent_abandons' => $this->recentAbandons($siteId, 25),
        ];
    }

    public function ensureDefaultCampaign(int $siteId): array
    {
        if (!AnalyticsSchema::hasTable('analytics_recovery_campaigns')) {
            return ['created' => false, 'campaign' => null];
        }

        $existing = DB::table('analytics_recovery_campaigns')
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return ['created' => false, 'campaign' => $existing];
        }

        $id = DB::table('analytics_recovery_campaigns')->insertGetId([
            'site_id' => $siteId,
            'name' => 'Default cart recovery',
            'channel' => 'email',
            'delays_minutes' => json_encode(config('analytics_enterprise.recovery.default_delays_minutes', [60, 1440])),
            'template' => json_encode([
                'subject' => 'You left something in your cart',
                'body' => 'Complete your order before items sell out.',
            ]),
            'discount_percent' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'created' => true,
            'campaign' => DB::table('analytics_recovery_campaigns')->where('id', $id)->first(),
        ];
    }

    public function segmentsOverview(int $siteId): array
    {
        $rfm = $this->advanced->rfmSegments();
        $mapped = [];
        foreach ($rfm['segments'] ?? [] as $row) {
            $slug = $this->normalizeSegmentSlug($row['segment'] ?? 'other');
            $mapped[$slug] = ($mapped[$slug] ?? 0) + (int) ($row['customers'] ?? 0);
        }

        $cards = [];
        foreach (['vip', 'loyal', 'promising', 'at_risk', 'hibernating', 'new', 'other'] as $slug) {
            if (!isset($mapped[$slug]) && !in_array($slug, ['vip', 'at_risk', 'new', 'loyal'], true)) {
                continue;
            }
            $cards[] = [
                'segment_slug' => $slug,
                'label' => $this->segmentLabel($slug),
                'customers' => (int) ($mapped[$slug] ?? 0),
                'description' => $this->segmentDescription($slug),
            ];
        }

        usort($cards, fn ($a, $b) => $b['customers'] <=> $a['customers']);

        $stored = [];
        if (AnalyticsSchema::hasTable('analytics_customer_scores')) {
            $stored = DB::table('analytics_customer_scores')
                ->where('site_id', $siteId)
                ->select('segment_slug', DB::raw('COUNT(*) as customers'), DB::raw('AVG(ltv) as avg_ltv'))
                ->groupBy('segment_slug')
                ->get()
                ->map(fn ($r) => [
                    'segment_slug' => $r->segment_slug,
                    'customers' => (int) $r->customers,
                    'avg_ltv' => round((float) $r->avg_ltv, 2),
                ])
                ->all();
        }

        return [
            'cards' => $cards,
            'customers' => array_slice($rfm['customers'] ?? [], 0, 40),
            'stored' => $stored,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    public function syncSegmentScores(int $siteId): array
    {
        if (!AnalyticsSchema::hasTable('analytics_customer_scores')) {
            return ['synced' => 0, 'message' => 'analytics_customer_scores table missing'];
        }

        $rfm = $this->advanced->rfmSegments();
        $now = now();
        $synced = 0;

        foreach ($rfm['customers'] ?? [] as $customer) {
            $email = $customer['email'] ?? null;
            $userId = null;
            if ($email) {
                $userId = User::query()->where('email', $email)->value('id');
            }
            if (!$userId && isset($customer['user_id'])) {
                $userId = (int) $customer['user_id'];
            }
            if (!$userId) {
                continue;
            }

            $slug = $this->normalizeSegmentSlug($customer['segment'] ?? 'other');
            $payload = [
                'ltv' => (float) ($customer['monetary'] ?? 0),
                'rfm_recency' => null,
                'rfm_frequency' => null,
                'rfm_monetary' => null,
                'segment_slug' => $slug,
                'calculated_at' => $now,
                'updated_at' => $now,
            ];

            $existing = DB::table('analytics_customer_scores')
                ->where('site_id', $siteId)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                DB::table('analytics_customer_scores')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('analytics_customer_scores')->insert($payload + [
                    'site_id' => $siteId,
                    'user_id' => $userId,
                    'created_at' => $now,
                ]);
            }
            $synced++;
        }

        return ['synced' => $synced, 'message' => "Synced {$synced} customer scores"];
    }

    public function attributionReport(int $siteId, string $from, string $to, string $model = 'last_click'): array
    {
        $channels = [];
        $fromTouches = false;

        if (AnalyticsSchema::hasTable('analytics_attribution_touches')) {
            $tz = config('app.timezone', 'UTC');
            $fromAt = Carbon::parse($from, $tz)->startOfDay();
            $toAt = Carbon::parse($to, $tz)->endOfDay();

            $rows = DB::table('analytics_attribution_touches')
                ->where('site_id', $siteId)
                ->whereBetween('touched_at', [$fromAt, $toAt])
                ->select(
                    'source',
                    DB::raw('SUM(revenue) as revenue'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('COUNT(*) as touches')
                )
                ->groupBy('source')
                ->orderByDesc('revenue')
                ->get();

            if ($rows->isNotEmpty()) {
                $fromTouches = true;
                $channels = $rows->map(fn ($r) => [
                    'source' => $r->source ?: 'direct',
                    'revenue' => (float) $r->revenue,
                    'orders' => (int) $r->orders,
                    'touches' => (int) $r->touches,
                    'spend' => 0.0,
                    'roas' => null,
                ])->all();
            }
        }

        if ($channels === []) {
            $channels = $this->attributionFromSessions($siteId, $from, $to);
        }

        $spendBySource = [];
        if (AnalyticsSchema::hasTable('analytics_campaign_costs')) {
            $spendBySource = DB::table('analytics_campaign_costs')
                ->where('site_id', $siteId)
                ->whereBetween('cost_date', [$from, $to])
                ->select('source', DB::raw('SUM(spend) as spend'))
                ->groupBy('source')
                ->pluck('spend', 'source')
                ->all();
        }

        foreach ($channels as &$ch) {
            $spend = (float) ($spendBySource[$ch['source']] ?? 0);
            $ch['spend'] = $spend;
            $ch['roas'] = $spend > 0 ? round(((float) $ch['revenue']) / $spend, 2) : null;
        }
        unset($ch);

        $totalRevenue = array_sum(array_column($channels, 'revenue'));
        $totalSpend = array_sum(array_column($channels, 'spend'));

        return [
            'model' => $model,
            'source' => $fromTouches ? 'attribution_touches' : 'sessions_last_click',
            'channels' => $channels,
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'spend' => round($totalSpend, 2),
                'roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : null,
            ],
        ];
    }

    public function saveCampaignCost(int $siteId, array $data): array
    {
        if (!AnalyticsSchema::hasTable('analytics_campaign_costs')) {
            throw new \RuntimeException('Campaign costs table missing');
        }

        $id = DB::table('analytics_campaign_costs')->insertGetId([
            'site_id' => $siteId,
            'cost_date' => $data['cost_date'] ?? now()->toDateString(),
            'source' => $data['source'] ?? 'ads',
            'campaign' => $data['campaign'] ?? null,
            'spend' => (float) ($data['spend'] ?? 0),
            'currency' => $data['currency'] ?? config('app.currency', 'PKR'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('analytics_campaign_costs')->where('id', $id)->first();
    }

    public function recentJourneyVisitors(int $siteId, int $limit = 30): array
    {
        if (!AnalyticsSchema::hasTable('analytics_visitors')) {
            return [];
        }

        return DB::table('analytics_visitors as v')
            ->leftJoin('analytics_events as e', function ($join) use ($siteId) {
                $join->on('e.visitor_id', '=', 'v.id')->where('e.site_id', '=', $siteId);
            })
            ->where('v.site_id', $siteId)
            ->select(
                'v.visitor_uuid',
                'v.user_id',
                DB::raw('COUNT(e.id) as events'),
                DB::raw('MAX(e.occurred_at) as last_seen')
            )
            ->groupBy('v.id', 'v.visitor_uuid', 'v.user_id')
            ->orderByDesc(DB::raw('MAX(e.occurred_at)'))
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'visitor_uuid' => $r->visitor_uuid,
                'user_id' => $r->user_id,
                'events' => (int) $r->events,
                'last_seen' => $r->last_seen,
            ])
            ->all();
    }

    public function alertRules(int $siteId): array
    {
        if (!AnalyticsSchema::hasTable('analytics_alert_rules')) {
            return [];
        }

        return DB::table('analytics_alert_rules')
            ->where('site_id', $siteId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'metric' => $r->metric,
                'operator' => $r->operator,
                'threshold' => (float) $r->threshold,
                'channels' => json_decode($r->channels ?? '[]', true) ?: [],
                'is_active' => (bool) $r->is_active,
            ])
            ->all();
    }

    public function storeAlertRule(int $siteId, array $data): array
    {
        if (!AnalyticsSchema::hasTable('analytics_alert_rules')) {
            throw new \RuntimeException('Alert rules table missing');
        }

        $id = DB::table('analytics_alert_rules')->insertGetId([
            'site_id' => $siteId,
            'name' => $data['name'] ?? 'New alert',
            'metric' => $data['metric'] ?? 'orders',
            'operator' => $data['operator'] ?? '<',
            'threshold' => (float) ($data['threshold'] ?? 0),
            'channels' => json_encode($data['channels'] ?? ['email']),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return collect($this->alertRules($siteId))->firstWhere('id', $id) ?? [];
    }

    public function toggleAlertRule(int $siteId, int $id, bool $active): bool
    {
        if (!AnalyticsSchema::hasTable('analytics_alert_rules')) {
            return false;
        }

        return DB::table('analytics_alert_rules')
            ->where('site_id', $siteId)
            ->where('id', $id)
            ->update(['is_active' => $active, 'updated_at' => now()]) > 0;
    }

    public function recentIncidents(int $siteId, int $limit = 20): array
    {
        if (!AnalyticsSchema::hasTable('analytics_alert_incidents')) {
            return [];
        }

        return DB::table('analytics_alert_incidents as i')
            ->leftJoin('analytics_alert_rules as r', 'r.id', '=', 'i.rule_id')
            ->where('i.site_id', $siteId)
            ->orderByDesc('i.fired_at')
            ->limit($limit)
            ->get([
                'i.id',
                'i.observed_value',
                'i.severity',
                'i.message',
                'i.fired_at',
                'i.acknowledged_at',
                'r.name as rule_name',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function seedDefaultAlerts(int $siteId): array
    {
        if (!AnalyticsSchema::hasTable('analytics_alert_rules')) {
            return ['created' => 0];
        }

        $defaults = [
            ['name' => 'Low daily orders', 'metric' => 'orders', 'operator' => '<', 'threshold' => 5],
            ['name' => 'High cart abandonment', 'metric' => 'cart_abandonment_rate', 'operator' => '>', 'threshold' => 70],
            ['name' => 'Conversion drop', 'metric' => 'conversion_rate', 'operator' => '<', 'threshold' => 1],
        ];

        $created = 0;
        foreach ($defaults as $rule) {
            $exists = DB::table('analytics_alert_rules')
                ->where('site_id', $siteId)
                ->where('name', $rule['name'])
                ->exists();
            if ($exists) {
                continue;
            }
            $this->storeAlertRule($siteId, $rule + ['channels' => ['email'], 'is_active' => true]);
            $created++;
        }

        return ['created' => $created, 'rules' => $this->alertRules($siteId)];
    }

    private function recentAbandons(int $siteId, int $limit): array
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return [];
        }

        return AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->whereIn('event_name', ['checkout_abandoned', 'checkout_started'])
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get(['id', 'event_name', 'occurred_at', 'page_url', 'visitor_id', 'user_id', 'revenue', 'properties'])
            ->map(function ($e) {
                $props = is_array($e->properties) ? $e->properties : (json_decode($e->properties ?? '{}', true) ?: []);

                return [
                    'id' => $e->id,
                    'event' => $e->event_name,
                    'occurred_at' => $e->occurred_at?->toIso8601String(),
                    'page_url' => $e->page_url,
                    'user_id' => $e->user_id,
                    'visitor_id' => $e->visitor_id,
                    'value' => (float) ($e->revenue ?: ($props['cart_total'] ?? $props['value'] ?? 0)),
                    'email' => $props['email'] ?? null,
                ];
            })
            ->all();
    }

    private function estimateRecoverableValue(int $siteId, string $from, string $to): float
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return 0;
        }

        $tz = config('app.timezone', 'UTC');
        $sum = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'checkout_abandoned')
            ->whereBetween('occurred_at', [
                Carbon::parse($from, $tz)->startOfDay(),
                Carbon::parse($to, $tz)->endOfDay(),
            ])
            ->sum('revenue');

        return round((float) $sum, 2);
    }

    private function attributionFromSessions(int $siteId, string $from, string $to): array
    {
        $sessions = $this->dashboard->sources($siteId, $from, $to);
        $orderRevenueBySource = $this->orderRevenueBySessionSource($siteId, $from, $to);

        return collect($sessions)->map(function ($row) use ($orderRevenueBySource) {
            $source = $row['source'] ?: 'direct';

            return [
                'source' => $source,
                'sessions' => (int) ($row['sessions'] ?? 0),
                'revenue' => (float) ($orderRevenueBySource[$source] ?? 0),
                'orders' => (int) ($orderRevenueBySource[$source . '__orders'] ?? 0),
                'touches' => (int) ($row['sessions'] ?? 0),
                'spend' => 0.0,
                'roas' => null,
            ];
        })->values()->all();
    }

    private function orderRevenueBySessionSource(int $siteId, string $from, string $to): array
    {
        if (!AnalyticsSchema::hasTable('analytics_events') || !AnalyticsSchema::hasTable('analytics_sessions')) {
            return [];
        }

        $tz = config('app.timezone', 'UTC');
        $rows = DB::table('analytics_events as e')
            ->join('analytics_sessions as s', 's.id', '=', 'e.session_id')
            ->where('e.site_id', $siteId)
            ->whereIn('e.event_name', ['order_placed', 'order_confirmed'])
            ->whereBetween('e.occurred_at', [
                Carbon::parse($from, $tz)->startOfDay(),
                Carbon::parse($to, $tz)->endOfDay(),
            ])
            ->select(
                DB::raw("COALESCE(NULLIF(s.source, ''), 'direct') as source"),
                DB::raw('SUM(COALESCE(e.revenue, 0)) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy(DB::raw("COALESCE(NULLIF(s.source, ''), 'direct')"))
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[$row->source] = (float) $row->revenue;
            $out[$row->source . '__orders'] = (int) $row->orders;
        }

        return $out;
    }

    private function countEvents(int $siteId, string $name, string $from, string $to): int
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return 0;
        }
        $tz = config('app.timezone', 'UTC');

        return (int) AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', $name)
            ->whereBetween('occurred_at', [
                Carbon::parse($from, $tz)->startOfDay(),
                Carbon::parse($to, $tz)->endOfDay(),
            ])
            ->count();
    }

    private function normalizeSegmentSlug(string $label): string
    {
        $l = strtolower($label);
        if (str_contains($l, 'champion') || str_contains($l, 'vip')) {
            return 'vip';
        }
        if (str_contains($l, 'loyal')) {
            return 'loyal';
        }
        if (str_contains($l, 'promising') || str_contains($l, 'potential')) {
            return 'promising';
        }
        if (str_contains($l, 'risk') || str_contains($l, 'about to sleep')) {
            return 'at_risk';
        }
        if (str_contains($l, 'hibernat') || str_contains($l, 'lost')) {
            return 'hibernating';
        }
        if (str_contains($l, 'new') || str_contains($l, 'recent')) {
            return 'new';
        }

        return Str::slug($label) ?: 'other';
    }

    private function segmentLabel(string $slug): string
    {
        return match ($slug) {
            'vip' => 'VIP / Champions',
            'loyal' => 'Loyal',
            'promising' => 'Promising',
            'at_risk' => 'At risk',
            'hibernating' => 'Hibernating',
            'new' => 'New',
            default => ucfirst(str_replace('_', ' ', $slug)),
        };
    }

    private function segmentDescription(string $slug): string
    {
        return match ($slug) {
            'vip' => 'High spend, frequent buyers — treat like Shopify VIP',
            'loyal' => 'Repeat purchasers with strong engagement',
            'promising' => 'Growing order frequency or spend',
            'at_risk' => 'Were active, now cooling off — win-back priority',
            'hibernating' => 'Long inactive — re-engagement campaigns',
            'new' => 'Recently acquired customers',
            default => 'Other RFM bucket',
        };
    }
}
