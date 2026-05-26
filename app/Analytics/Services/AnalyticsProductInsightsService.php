<?php

namespace App\Analytics\Services;

use App\Analytics\Models\AnalyticsEvent;
use App\Enums\Ask;
use App\Enums\PaymentStatus;
use App\Enums\Status;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsProductInsightsService
{
    private const PRODUCT_EVENTS = [
        'product_viewed',
        'add_to_cart',
        'remove_from_cart',
        'add_to_wishlist',
        'remove_wishlist',
        'checkout_started',
        'order_placed',
        'order_confirmed',
    ];

    public function catalog(int $siteId, string $from, string $to, ?string $search = null): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $eventAgg = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->whereBetween('occurred_at', [$fromAt, $toAt])
            ->whereNotNull('product_id')
            ->whereIn('event_name', self::PRODUCT_EVENTS)
            ->select('product_id', 'event_name', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id', 'event_name')
            ->get()
            ->groupBy('product_id');

        $orderClass = Order::class;
        $salesAgg = DB::table('stocks')
            ->join('orders', function ($join) use ($orderClass) {
                $join->on('stocks.model_id', '=', 'orders.id')
                    ->where('stocks.model_type', '=', $orderClass);
            })
            ->where('orders.active', Ask::YES)
            ->where('orders.payment_status', PaymentStatus::PAID)
            ->whereBetween('orders.order_datetime', [$fromAt, $toAt])
            ->select(
                'stocks.product_id',
                DB::raw('SUM(stocks.quantity) as units_sold'),
                DB::raw('SUM(stocks.total) as revenue')
            )
            ->groupBy('stocks.product_id')
            ->get()
            ->keyBy('product_id');

        $query = Product::query()
            ->withSum('stockItems as stock_qty', 'quantity')
            ->where('status', Status::ACTIVE);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('name')->limit(500)->get()->map(function (Product $product) use ($eventAgg, $salesAgg) {
            $pid = $product->id;
            $events = $eventAgg->get($pid, collect());
            $byName = $events->pluck('total', 'event_name');

            $views = (int) ($byName['product_viewed'] ?? 0);
            $addToCart = (int) ($byName['add_to_cart'] ?? 0);
            $removeCart = (int) ($byName['remove_from_cart'] ?? 0);
            $checkout = (int) ($byName['checkout_started'] ?? 0);
            $ordersTracked = (int) (($byName['order_placed'] ?? 0) + ($byName['order_confirmed'] ?? 0));
            $wishlist = (int) (($byName['add_to_wishlist'] ?? 0) - ($byName['remove_wishlist'] ?? 0));

            $sale = $salesAgg->get($pid);
            $unitsSold = (int) ($sale->units_sold ?? 0);
            $revenue = (float) ($sale->revenue ?? 0);

            $viewToCart = $views > 0 ? round(($addToCart / $views) * 100, 2) : 0;
            $cartToOrder = $addToCart > 0 ? round(($unitsSold / $addToCart) * 100, 2) : 0;

            return [
                'product_id' => $pid,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => (int) ($product->stock_qty ?? 0),
                'page_views' => $views,
                'add_to_cart' => $addToCart,
                'remove_from_cart' => $removeCart,
                'checkout_started' => $checkout,
                'orders_tracked' => $ordersTracked,
                'units_sold' => $unitsSold,
                'revenue' => $revenue,
                'wishlist_net' => max(0, $wishlist),
                'view_to_cart_rate' => $viewToCart,
                'cart_to_purchase_rate' => $cartToOrder,
            ];
        })->sortByDesc('page_views')->values()->all();
    }

    public function detail(int $siteId, int $productId, string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $product = Product::query()
            ->withSum('stockItems as stock_qty', 'quantity')
            ->findOrFail($productId);

        $metrics = [];
        foreach (self::PRODUCT_EVENTS as $eventName) {
            $metrics[$eventName] = (int) AnalyticsEvent::query()
                ->where('site_id', $siteId)
                ->where('product_id', $productId)
                ->where('event_name', $eventName)
                ->whereBetween('occurred_at', [$fromAt, $toAt])
                ->count();
        }

        $daily = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('product_id', $productId)
            ->whereIn('event_name', ['product_viewed', 'add_to_cart', 'order_placed'])
            ->whereBetween('occurred_at', [$fromAt, $toAt])
            ->select(
                DB::raw('DATE(occurred_at) as day'),
                'event_name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('day', 'event_name')
            ->orderBy('day')
            ->get();

        $series = [];
        for ($current = strtotime($from); $current <= strtotime($to); $current += 86400) {
            $day = date('Y-m-d', $current);
            $dayRows = $daily->where('day', $day);
            $series[] = [
                'date' => $day,
                'views' => (int) $dayRows->where('event_name', 'product_viewed')->sum('total'),
                'add_to_cart' => (int) $dayRows->where('event_name', 'add_to_cart')->sum('total'),
                'orders' => (int) $dayRows->where('event_name', 'order_placed')->sum('total'),
            ];
        }

        $topPages = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('product_id', $productId)
            ->where('event_name', 'product_viewed')
            ->whereBetween('occurred_at', [$fromAt, $toAt])
            ->whereNotNull('page_url')
            ->select('page_url', DB::raw('COUNT(*) as views'))
            ->groupBy('page_url')
            ->orderByDesc('views')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['url' => $r->page_url, 'views' => (int) $r->views])
            ->all();

        $orderClass = Order::class;
        $commerce = DB::table('stocks')
            ->join('orders', function ($join) use ($orderClass) {
                $join->on('stocks.model_id', '=', 'orders.id')
                    ->where('stocks.model_type', '=', $orderClass);
            })
            ->where('stocks.product_id', $productId)
            ->where('orders.active', Ask::YES)
            ->where('orders.payment_status', PaymentStatus::PAID)
            ->whereBetween('orders.order_datetime', [$fromAt, $toAt])
            ->select(
                DB::raw('SUM(stocks.quantity) as units'),
                DB::raw('SUM(stocks.total) as revenue')
            )
            ->first();

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => (int) ($product->stock_qty ?? 0),
            ],
            'metrics' => $metrics,
            'daily_series' => $series,
            'top_pages' => $topPages,
            'commerce' => [
                'units_sold' => (int) ($commerce->units ?? 0),
                'revenue' => (float) ($commerce->revenue ?? 0),
            ],
            'funnel' => [
                ['step' => 'Product views', 'count' => $metrics['product_viewed'] ?? 0],
                ['step' => 'Add to cart', 'count' => $metrics['add_to_cart'] ?? 0],
                ['step' => 'Checkout started', 'count' => $metrics['checkout_started'] ?? 0],
                ['step' => 'Order (tracked)', 'count' => ($metrics['order_placed'] ?? 0) + ($metrics['order_confirmed'] ?? 0)],
                ['step' => 'Sold (store)', 'count' => (int) ($commerce->units ?? 0)],
            ],
        ];
    }
}
