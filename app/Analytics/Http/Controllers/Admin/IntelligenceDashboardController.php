<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsDashboardService;
use App\Analytics\Services\AnalyticsRealtimeService;
use App\Analytics\Services\AnalyticsSettingsService;
use App\Enums\Ask;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IntelligenceDashboardController extends AdminController
{
    public function __construct(
        private readonly AnalyticsDashboardService $dashboard,
        private readonly EloquentAnalyticsSiteRepository $sites,
        private readonly AnalyticsSettingsService $settings,
    ) {}

    public function sites(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $list = $this->sites->listForUser($userId);

            if (empty($list)) {
                $this->settings->resolveOrCreateSite($userId);
                $list = $this->sites->listForUser($userId);
            }

            $today = Carbon::now(config('app.timezone', 'UTC'))->toDateString();
            $defaultFrom = Carbon::parse($today, config('app.timezone', 'UTC'))->subDays(6)->toDateString();
            $defaultSiteId = !empty($list) ? $list[0]->id : null;

            return response()->json([
                'success' => true,
                'data' => collect($list)->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'domain' => $s->domain,
                    'public_key' => $s->public_key,
                    'is_active' => (bool) $s->is_active,
                ])->values(),
                'meta' => [
                    'default_site_id' => $defaultSiteId,
                    'server_today' => $today,
                    'default_from' => $defaultFrom,
                    'default_to' => $today,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Could not load analytics sites.',
            ], 500);
        }
    }

    public function overview(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $data = $this->dashboard->overview($site->id, $from, $to);
        $commerce = $this->commerceMetrics($from, $to);

        $data['tracking_revenue'] = $data['revenue'];
        $data['tracking_orders'] = $data['orders'];
        $data['revenue'] = $commerce['period_revenue'];
        $data['orders'] = $commerce['period_orders'];
        $data['all_time_revenue'] = $commerce['all_time_revenue'];
        $data['all_time_orders'] = $commerce['all_time_orders'];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function realtime(Request $request, AnalyticsRealtimeService $realtime): JsonResponse
    {
        $site = $this->resolveSite($request);

        return response()->json([
            'success' => true,
            'data' => $realtime->snapshot($site->id),
        ]);
    }

    public function funnel(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->funnel($site->id, $from, $to),
        ]);
    }

    public function sources(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->sources($site->id, $from, $to),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->topProducts($site->id, $from, $to),
        ]);
    }

    public function dailySeries(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(6)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $rows = $this->dashboard->dailySeries($site->id, $from, $to);
        if ($rows === []) {
            $rows = $this->commerceDailySeries($from, $to);
        } else {
            $rows = $this->enrichDailySeriesWithCommerce($rows, $from, $to);
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /** Same source as Laravel admin dashboard (orders table). */
    private function commerceMetrics(string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $periodBase = Order::query()
            ->where('active', Ask::YES)
            ->whereBetween('order_datetime', [$fromAt, $toAt]);

        return [
            'period_revenue' => (float) (clone $periodBase)
                ->where('payment_status', PaymentStatus::PAID)
                ->sum('total'),
            'period_orders' => (int) (clone $periodBase)->count(),
            'all_time_revenue' => (float) Order::query()
                ->where('active', Ask::YES)
                ->where('payment_status', PaymentStatus::PAID)
                ->sum('total'),
            'all_time_orders' => (int) Order::query()
                ->where('active', Ask::YES)
                ->count(),
        ];
    }

    private function commerceDailySeries(string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $revenueByDay = Order::query()
            ->where('active', Ask::YES)
            ->where('payment_status', PaymentStatus::PAID)
            ->whereBetween('order_datetime', [$fromAt, $toAt])
            ->select(DB::raw('DATE(order_datetime) as metric_date'), DB::raw('SUM(total) as revenue'))
            ->groupBy('metric_date')
            ->pluck('revenue', 'metric_date');

        $ordersByDay = Order::query()
            ->where('active', Ask::YES)
            ->whereBetween('order_datetime', [$fromAt, $toAt])
            ->select(DB::raw('DATE(order_datetime) as metric_date'), DB::raw('COUNT(*) as orders'))
            ->groupBy('metric_date')
            ->pluck('orders', 'metric_date');

        $series = [];
        for ($current = strtotime($from); $current <= strtotime($to); $current += 86400) {
            $date = date('Y-m-d', $current);
            $series[] = [
                'date' => $date,
                'visitors' => 0,
                'sessions' => 0,
                'page_views' => 0,
                'orders' => (int) ($ordersByDay[$date] ?? 0),
                'revenue' => (float) ($revenueByDay[$date] ?? 0),
            ];
        }

        return $series;
    }

    private function enrichDailySeriesWithCommerce(array $rows, string $from, string $to): array
    {
        $commerce = collect($this->commerceDailySeries($from, $to))->keyBy('date');

        return array_map(function (array $row) use ($commerce) {
            $c = $commerce->get($row['date']);
            if ($c && ((float) ($row['revenue'] ?? 0)) <= 0) {
                $row['revenue'] = $c['revenue'];
            }
            if ($c && ((int) ($row['orders'] ?? 0)) <= 0) {
                $row['orders'] = $c['orders'];
            }

            return $row;
        }, $rows);
    }

    private function resolveSite(Request $request)
    {
        $userId = $request->user()->id;
        $siteId = (int) $request->input('site_id', $request->header('X-Analytics-Site-Id'));

        if ($siteId > 0) {
            $site = $this->sites->findForUser($siteId, $userId);
            if ($site) {
                return $site;
            }
        }

        $list = $this->sites->listForUser($userId);
        if (!empty($list)) {
            return $list[0];
        }

        abort(404, 'Analytics site not found');
    }
}
