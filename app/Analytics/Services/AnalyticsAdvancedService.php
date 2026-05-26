<?php

namespace App\Analytics\Services;

use App\Analytics\Models\AnalyticsDevice;
use App\Analytics\Models\AnalyticsSession;
use App\Analytics\Models\AnalyticsSite;
use App\Enums\Ask;
use App\Enums\PaymentStatus;
use App\Enums\Role as EnumRole;
use App\Models\Order;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\Product;
use App\Models\ReturnAndRefund;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsAdvancedService
{
    public function cohortRetention(int $months = 6): array
    {
        $tz = config('app.timezone', 'UTC');
        $start = Carbon::now($tz)->subMonths($months - 1)->startOfMonth();

        $firstOrders = Order::query()
            ->where('active', Ask::YES)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MIN(order_datetime) as first_order_at'))
            ->groupBy('user_id');

        $cohortUsers = DB::query()
            ->fromSub($firstOrders, 'fo')
            ->where('first_order_at', '>=', $start)
            ->select('user_id', DB::raw('DATE_FORMAT(first_order_at, "%Y-%m") as cohort_month'))
            ->get()
            ->groupBy('cohort_month');

        $allOrders = Order::query()
            ->where('active', Ask::YES)
            ->whereNotNull('user_id')
            ->where('order_datetime', '>=', $start)
            ->select('user_id', 'order_datetime')
            ->get()
            ->groupBy('user_id');

        $cohorts = [];
        foreach ($cohortUsers as $cohortMonth => $users) {
            $cohortStart = Carbon::parse($cohortMonth . '-01', $tz)->startOfMonth();
            $userIds = $users->pluck('user_id')->all();
            $size = count($userIds);
            $retention = [];

            for ($m = 0; $m <= 5; $m++) {
                $periodStart = $cohortStart->copy()->addMonths($m)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $returned = 0;

                foreach ($userIds as $uid) {
                    $orders = $allOrders->get($uid, collect());
                    $has = $orders->contains(function ($o) use ($periodStart, $periodEnd) {
                        return Carbon::parse($o->order_datetime)->between($periodStart, $periodEnd);
                    });
                    if ($has) {
                        $returned++;
                    }
                }

                $retention[] = [
                    'period' => $m === 0 ? 'Month 0' : 'Month ' . $m,
                    'customers' => $returned,
                    'rate' => $size > 0 ? round(($returned / $size) * 100, 1) : 0,
                ];
            }

            $cohorts[] = [
                'cohort' => $cohortMonth,
                'size' => $size,
                'retention' => $retention,
            ];
        }

        return array_values(collect($cohorts)->sortByDesc('cohort')->take($months)->values()->all());
    }

    public function rfmSegments(): array
    {
        $now = Carbon::now(config('app.timezone', 'UTC'));
        $rows = Order::query()
            ->where('active', Ask::YES)
            ->where('payment_status', PaymentStatus::PAID)
            ->whereNotNull('user_id')
            ->select(
                'user_id',
                DB::raw('MAX(order_datetime) as last_order_at'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as monetary')
            )
            ->groupBy('user_id')
            ->get();

        if ($rows->isEmpty()) {
            return ['segments' => [], 'customers' => []];
        }

        $scored = $rows->map(function ($row) use ($now) {
            $recencyDays = $now->diffInDays(Carbon::parse($row->last_order_at));
            $frequency = (int) $row->order_count;
            $monetary = (float) $row->monetary;

            return [
                'user_id' => (int) $row->user_id,
                'recency_days' => $recencyDays,
                'frequency' => $frequency,
                'monetary' => $monetary,
            ];
        });

        $rScores = $this->quintileScores($scored->pluck('recency_days')->all(), true);
        $fScores = $this->quintileScores($scored->pluck('frequency')->all(), false);
        $mScores = $this->quintileScores($scored->pluck('monetary')->all(), false);

        $segmentCounts = [];
        $customers = [];

        foreach ($scored->values() as $i => $row) {
            $r = $rScores[$i];
            $f = $fScores[$i];
            $m = $mScores[$i];
            $segment = $this->rfmLabel($r, $f, $m);
            $segmentCounts[$segment] = ($segmentCounts[$segment] ?? 0) + 1;

            if (count($customers) < 50) {
                $user = User::query()->find($row['user_id']);
                $customers[] = [
                    'name' => $user?->name ?? 'Customer #' . $row['user_id'],
                    'email' => $user?->email,
                    'segment' => $segment,
                    'recency_days' => $row['recency_days'],
                    'frequency' => $row['frequency'],
                    'monetary' => $row['monetary'],
                ];
            }
        }

        $segments = collect($segmentCounts)->map(fn ($count, $label) => [
            'segment' => $label,
            'customers' => $count,
        ])->sortByDesc('customers')->values()->all();

        usort($customers, fn ($a, $b) => $b['monetary'] <=> $a['monetary']);

        return ['segments' => $segments, 'customers' => $customers];
    }

    public function productAffinity(int $limit = 15): array
    {
        $orderClass = Order::class;
        $pairs = DB::table('stocks as a')
            ->join('stocks as b', function ($join) {
                $join->on('a.model_id', '=', 'b.model_id')
                    ->on('a.model_type', '=', 'b.model_type')
                    ->whereColumn('a.product_id', '<', 'b.product_id');
            })
            ->where('a.model_type', $orderClass)
            ->where('b.model_type', $orderClass)
            ->whereNotNull('a.product_id')
            ->whereNotNull('b.product_id')
            ->select('a.product_id as product_a', 'b.product_id as product_b', DB::raw('COUNT(*) as times'))
            ->groupBy('a.product_id', 'b.product_id')
            ->orderByDesc('times')
            ->limit($limit)
            ->get();

        $ids = $pairs->flatMap(fn ($p) => [$p->product_a, $p->product_b])->unique()->all();
        $names = Product::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $pairs->map(fn ($p) => [
            'product_a' => $names[$p->product_a] ?? '#' . $p->product_a,
            'product_b' => $names[$p->product_b] ?? '#' . $p->product_b,
            'times_bought_together' => (int) $p->times,
        ])->values()->all();
    }

    public function paymentSplit(string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $rows = Order::query()
            ->where('active', Ask::YES)
            ->whereBetween('order_datetime', [$fromAt, $toAt])
            ->select('payment_method', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')
            ->get();

        $gateways = PaymentGatewayModel::query()->pluck('name', 'id');

        $items = $rows->map(function ($row) use ($gateways) {
            $id = (int) $row->payment_method;
            $isCod = $id === \App\Enums\PaymentGateway::CASH_ON_DELIVERY;

            return [
                'payment_method_id' => $id,
                'name' => $gateways[$id] ?? ($isCod ? 'Cash on delivery' : 'Gateway #' . $id),
                'is_cod' => $isCod,
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
            ];
        })->values()->all();

        $cod = collect($items)->where('is_cod', true);
        $prepaid = collect($items)->where('is_cod', false);

        return [
            'methods' => $items,
            'summary' => [
                'cod_orders' => $cod->sum('orders'),
                'cod_revenue' => $cod->sum('revenue'),
                'prepaid_orders' => $prepaid->sum('orders'),
                'prepaid_revenue' => $prepaid->sum('revenue'),
            ],
        ];
    }

    public function returnAnalytics(string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $ordersInRange = Order::query()
            ->where('active', Ask::YES)
            ->whereBetween('order_datetime', [$fromAt, $toAt])
            ->count();

        $returns = ReturnAndRefund::query()
            ->whereBetween('created_at', [$fromAt, $toAt])
            ->with('order:id,total')
            ->get();

        $lostRevenue = $returns->sum(fn ($r) => (float) ($r->order?->total ?? 0));

        return [
            'returns_count' => $returns->count(),
            'orders_in_period' => $ordersInRange,
            'return_rate' => $ordersInRange > 0
                ? round(($returns->count() / $ordersInRange) * 100, 2)
                : 0,
            'lost_revenue' => $lostRevenue,
            'recent' => $returns->take(20)->map(fn ($r) => [
                'order_serial' => $r->order_serial_no,
                'status' => $r->status,
                'amount' => (float) ($r->order?->total ?? 0),
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public function geoAndDevice(int $siteId, string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $sessionIds = AnalyticsSession::query()
            ->where('site_id', $siteId)
            ->whereBetween('started_at', [$fromAt, $toAt])
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return ['countries' => [], 'cities' => [], 'devices' => [], 'browsers' => []];
        }

        $devices = AnalyticsDevice::query()->whereIn('session_id', $sessionIds);

        $countries = (clone $devices)->select('country', DB::raw('COUNT(*) as sessions'))
            ->whereNotNull('country')->where('country', '!=', '')
            ->groupBy('country')->orderByDesc('sessions')->limit(10)->get();

        $cities = (clone $devices)->select('city', 'country', DB::raw('COUNT(*) as sessions'))
            ->whereNotNull('city')->where('city', '!=', '')
            ->groupBy('city', 'country')->orderByDesc('sessions')->limit(12)->get();

        $deviceTypes = (clone $devices)->select('device_type', DB::raw('COUNT(*) as sessions'))
            ->groupBy('device_type')->orderByDesc('sessions')->get();

        $browsers = (clone $devices)->select('browser', DB::raw('COUNT(*) as sessions'))
            ->groupBy('browser')->orderByDesc('sessions')->limit(8)->get();

        return [
            'countries' => $countries->map(fn ($r) => [
                'label' => $r->country,
                'sessions' => (int) $r->sessions,
            ])->all(),
            'cities' => $cities->map(fn ($r) => [
                'label' => trim($r->city . ($r->country ? ", {$r->country}" : '')),
                'sessions' => (int) $r->sessions,
            ])->all(),
            'devices' => $deviceTypes->map(fn ($r) => [
                'label' => $r->device_type ?: 'unknown',
                'sessions' => (int) $r->sessions,
            ])->all(),
            'browsers' => $browsers->map(fn ($r) => [
                'label' => $r->browser ?: 'unknown',
                'sessions' => (int) $r->sessions,
            ])->all(),
        ];
    }

    public function hourlyHeatmap(string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $orderRows = Order::query()
            ->where('active', Ask::YES)
            ->whereBetween('order_datetime', [$fromAt, $toAt])
            ->select(
                DB::raw('DAYOFWEEK(order_datetime) as dow'),
                DB::raw('HOUR(order_datetime) as hour'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('dow', 'hour')
            ->get();

        $sessionRows = AnalyticsSession::query()
            ->whereBetween('started_at', [$fromAt, $toAt])
            ->select(
                DB::raw('DAYOFWEEK(started_at) as dow'),
                DB::raw('HOUR(started_at) as hour'),
                DB::raw('COUNT(*) as sessions')
            )
            ->groupBy('dow', 'hour')
            ->get();

        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $grid = [];
        for ($d = 1; $d <= 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $grid[] = [
                    'day' => $days[$d - 1],
                    'hour' => $h,
                    'orders' => (int) $orderRows->firstWhere(fn ($r) => (int) $r->dow === $d && (int) $r->hour === $h)?->orders ?? 0,
                    'sessions' => (int) $sessionRows->firstWhere(fn ($r) => (int) $r->dow === $d && (int) $r->hour === $h)?->sessions ?? 0,
                ];
            }
        }

        $byHourOrders = [];
        for ($h = 0; $h < 24; $h++) {
            $byHourOrders[] = [
                'hour' => sprintf('%02d:00', $h),
                'orders' => (int) $orderRows->where('hour', $h)->sum('orders'),
            ];
        }

        return ['grid' => $grid, 'orders_by_hour' => $byHourOrders];
    }

    public function inventoryForecast(): array
    {
        $since = Carbon::now(config('app.timezone', 'UTC'))->subDays(30)->startOfDay();
        $orderClass = Order::class;

        $salesVelocity = DB::table('stocks')
            ->join('orders', function ($join) use ($orderClass) {
                $join->on('stocks.model_id', '=', 'orders.id')
                    ->where('stocks.model_type', '=', $orderClass);
            })
            ->where('orders.active', Ask::YES)
            ->where('orders.order_datetime', '>=', $since)
            ->select('stocks.product_id', DB::raw('SUM(stocks.quantity) as units_sold'))
            ->groupBy('stocks.product_id')
            ->pluck('units_sold', 'product_id');

        $products = Product::query()
            ->withSum('stockItems as stock_qty', 'quantity')
            ->orderBy('stock_qty')
            ->limit(80)
            ->get(['id', 'name', 'sku', 'low_stock_quantity_warning']);

        return $products->map(function (Product $p) use ($salesVelocity) {
            $stock = (int) ($p->stock_qty ?? 0);
            $sold30 = (int) ($salesVelocity[$p->id] ?? 0);
            $daily = $sold30 / 30;
            $daysLeft = $daily > 0 ? round($stock / $daily, 1) : null;

            return [
                'product_id' => $p->id,
                'name' => $p->name,
                'stock' => $stock,
                'sold_30d' => $sold30,
                'daily_velocity' => round($daily, 2),
                'days_until_stockout' => $daysLeft,
                'risk' => $daysLeft !== null && $daysLeft <= 7 ? 'high' : ($stock <= ($p->low_stock_quantity_warning ?: 10) ? 'medium' : 'low'),
            ];
        })->sortBy('days_until_stockout')->values()->take(40)->all();
    }

    public function multiStoreCompare(array $siteIds, string $from, string $to, AnalyticsDashboardService $dashboard): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $storeOrders = Order::query()
            ->where('active', Ask::YES)
            ->whereBetween('order_datetime', [$fromAt, $toAt]);

        $commerce = [
            'orders' => (int) (clone $storeOrders)->count(),
            'revenue' => (float) (clone $storeOrders)->where('payment_status', PaymentStatus::PAID)->sum('total'),
        ];

        $sites = AnalyticsSite::query()->whereIn('id', $siteIds)->get();

        return $sites->map(function (AnalyticsSite $site) use ($dashboard, $from, $to, $commerce) {
            $intel = $dashboard->overview($site->id, $from, $to);

            return [
                'site_id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'visitors' => $intel['visitors'],
                'sessions' => $intel['sessions'],
                'page_views' => $intel['page_views'],
                'tracking_orders' => $intel['orders'],
                'tracking_revenue' => $intel['revenue'],
                'store_orders' => $commerce['orders'],
                'store_revenue' => $commerce['revenue'],
            ];
        })->values()->all();
    }

    private function quintileScores(array $values, bool $lowerIsBetter): array
    {
        $sorted = collect($values)->sort()->values();
        $n = max(1, $sorted->count());

        return collect($values)->map(function ($v) use ($sorted, $n, $lowerIsBetter) {
            $rank = $sorted->search($v);
            $score = (int) min(5, max(1, floor(($rank / $n) * 5) + 1));

            return $lowerIsBetter ? (6 - $score) : $score;
        })->all();
    }

    private function rfmLabel(int $r, int $f, int $m): string
    {
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }
        if ($r >= 3 && $f >= 3) {
            return 'Loyal';
        }
        if ($r <= 2 && $f >= 3) {
            return 'At risk';
        }
        if ($r <= 2 && $f <= 2) {
            return 'Hibernating';
        }
        if ($m >= 4) {
            return 'Big spenders';
        }

        return 'Potential';
    }
}
