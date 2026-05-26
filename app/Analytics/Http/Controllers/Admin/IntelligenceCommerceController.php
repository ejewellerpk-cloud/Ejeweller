<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsDashboardService;
use App\Enums\Ask;
use App\Enums\Status;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

class IntelligenceCommerceController extends AdminController
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ProductService $products,
        private readonly AnalyticsDashboardService $analytics,
        private readonly EloquentAnalyticsSiteRepository $sites,
    ) {}

    public function totals(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_sales' => (float) $this->dashboard->totalSales(),
                    'total_orders' => (int) $this->dashboard->totalOrders(),
                    'total_customers' => (int) $this->dashboard->totalCustomers(),
                    'total_products' => (int) $this->dashboard->totalProducts(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function orderStatistics(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->dashboard->orderStatistics($this->withCommerceDates($request)),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function salesSummary(Request $request): JsonResponse
    {
        try {
            $raw = $this->dashboard->salesSummary($this->withCommerceDates($request));
            $dates = $this->dateRangeLabels($request);
            $series = [];
            foreach ($dates as $i => $date) {
                $series[] = [
                    'date' => $date,
                    'revenue' => (float) ($raw['per_day_sales'][$i] ?? 0),
                ];
            }

            $periodRevenue = 0.0;
            foreach ($raw['per_day_sales'] ?? [] as $amount) {
                $periodRevenue += (float) $amount;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_sales' => $periodRevenue,
                    'avg_per_day' => count($raw['per_day_sales'] ?? []) > 0
                        ? $periodRevenue / count($raw['per_day_sales'])
                        : 0,
                    'series' => $series,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function orderSummary(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->dashboard->orderSummary($this->withCommerceDates($request)),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function customerActivity(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->dashboard->customerStates($this->withCommerceDates($request)),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function topCustomers(): JsonResponse
    {
        try {
            $users = $this->dashboard->topCustomers();

            return response()->json([
                'success' => true,
                'data' => $users->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'orders_count' => (int) $u->orders_count,
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function topSellingProducts(): JsonResponse
    {
        try {
            $items = $this->products->topProducts();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Product $p) => [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'orders_count' => (int) ($p->order_countable_count ?? 0),
                    'stock' => (int) $p->stockItems()->sum('quantity'),
                    'low_stock_warning' => (int) ($p->low_stock_quantity_warning ?? 0),
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function lowStock(): JsonResponse
    {
        try {
            $products = Product::query()
                ->where('status', Status::ACTIVE)
                ->withSum('stockItems as stock_qty', 'quantity')
                ->orderBy('stock_qty')
                ->limit(100)
                ->get(['id', 'name', 'sku', 'low_stock_quantity_warning'])
                ->filter(function (Product $p) {
                    $stock = (int) ($p->stock_qty ?? 0);
                    $threshold = max(1, (int) ($p->low_stock_quantity_warning ?: 10));

                    return $stock <= $threshold;
                })
                ->take(50)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $products->map(fn (Product $p) => [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'stock' => (int) ($p->stock_qty ?? 0),
                    'threshold' => (int) ($p->low_stock_quantity_warning ?? 5),
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function cartInsights(Request $request): JsonResponse
    {
        try {
            $site = $this->resolveSite($request);
            $from = $request->input('from', now()->subDays(6)->toDateString());
            $to = $request->input('to', now()->toDateString());
            $funnel = $this->analytics->funnel($site->id, $from, $to);

            $byStep = collect($funnel)->keyBy('step');
            $carts = (int) ($byStep->get('Cart')['count'] ?? 0);
            $checkout = (int) ($byStep->get('Checkout')['count'] ?? 0);
            $orders = (int) ($byStep->get('Order')['count'] ?? 0);
            $abandonment = $carts > 0 ? round((($carts - $orders) / $carts) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'add_to_cart' => $carts,
                    'checkout_started' => $checkout,
                    'orders' => $orders,
                    'cart_abandonment_rate' => $abandonment,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $limit = min(50, max(5, (int) $request->input('limit', 20)));

            $orders = Order::query()
                ->where('active', Ask::YES)
                ->orderByDesc('order_datetime')
                ->limit($limit)
                ->get(['id', 'order_serial_no', 'total', 'order_datetime', 'status', 'payment_status']);

            return response()->json([
                'success' => true,
                'data' => $orders->map(fn (Order $o) => [
                    'id' => $o->id,
                    'serial' => $o->order_serial_no,
                    'total' => (float) $o->total,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'placed_at' => $o->order_datetime?->toIso8601String(),
                    'label' => '#' . $o->order_serial_no . ' — ' . number_format((float) $o->total, 2),
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function roles(): JsonResponse
    {
        try {
            $roles = Role::query()->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $roles->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => User::role($role->name)->count(),
                    'guard_name' => $role->guard_name,
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function exportReport(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        try {
            $site = $this->resolveSite($request);
            $from = $request->input('from', now()->subDays(6)->toDateString());
            $to = $request->input('to', now()->toDateString());

            $overview = $this->analytics->overview($site->id, $from, $to);
            $funnel = $this->analytics->funnel($site->id, $from, $to);
            $sources = $this->analytics->sources($site->id, $from, $to);
            $filename = 'shopperzz-analytics-' . $from . '-to-' . $to . '.csv';

            return response()->streamDownload(function () use ($overview, $funnel, $sources, $from, $to) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Shopperzz Analytics Report', $from, 'to', $to]);
                fputcsv($out, []);
                fputcsv($out, ['Metric', 'Value']);
                foreach ($overview as $key => $value) {
                    if (!is_array($value)) {
                        fputcsv($out, [$key, $value]);
                    }
                }
                fputcsv($out, []);
                fputcsv($out, ['Funnel step', 'Count', 'Conversion %']);
                foreach ($funnel as $row) {
                    fputcsv($out, [$row['step'] ?? '', $row['count'] ?? 0, $row['conversion_pct'] ?? 0]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Source', 'Sessions']);
                foreach ($sources as $row) {
                    fputcsv($out, [$row['source'] ?? '', $row['sessions'] ?? 0]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    private function withCommerceDates(Request $request): Request
    {
        $from = $request->input('from', $request->input('first_date', now()->subDays(6)->toDateString()));
        $to = $request->input('to', $request->input('last_date', now()->toDateString()));

        $request->merge([
            'first_date' => $from,
            'last_date' => $to,
        ]);

        return $request;
    }

    private function dateRangeLabels(Request $request): array
    {
        $from = $request->input('from', now()->subDays(6)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $labels = [];
        for ($current = strtotime($from); $current <= strtotime($to); $current += 86400) {
            $labels[] = date('Y-m-d', $current);
        }

        return $labels;
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

    private function fail(\Throwable $e): JsonResponse
    {
        report($e);

        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'Request failed.',
        ], 422);
    }
}
