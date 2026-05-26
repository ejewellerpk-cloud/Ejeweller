<?php

namespace App\Analytics\Services;

use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Support\ProductUrlAttribution;
use App\Enums\Ask;
use App\Enums\PaymentStatus;
use App\Enums\Status;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $fromDate = $fromAt->toDateString();
        $toDate = $toAt->toDateString();

        $eventAgg = ProductUrlAttribution::eventsGroupedByProductId(
            $siteId,
            $fromDate,
            $toDate,
            self::PRODUCT_EVENTS
        );
        $urlPageViews = ProductUrlAttribution::pageViewsByProductId($siteId, $fromDate, $toDate);

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

        return $query->orderBy('name')->limit(500)->get()->map(function (Product $product) use ($eventAgg, $salesAgg, $urlPageViews) {
            $pid = $product->id;
            $events = $eventAgg->get($pid, collect());
            $byName = $events->pluck('total', 'event_name');

            $views = ProductUrlAttribution::mergeViewCount(
                (int) ($byName['product_viewed'] ?? 0),
                (int) ($urlPageViews[$pid] ?? 0)
            );
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
        })->sortByDesc(fn ($row) => $row['page_views'] + $row['units_sold'])->values()->all();
    }

    public function detail(int $siteId, int $productId, string $from, string $to): array
    {
        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();
        $fromDate = $fromAt->toDateString();
        $toDate = $toAt->toDateString();

        $product = Product::query()
            ->withSum('stockItems as stock_qty', 'quantity')
            ->findOrFail($productId);

        $metrics = array_fill_keys(self::PRODUCT_EVENTS, 0);
        AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('product_id', $productId)
            ->whereIn('event_name', self::PRODUCT_EVENTS)
            ->whereBetween('event_date', [$fromDate, $toDate])
            ->select('event_name', DB::raw('COUNT(*) as total'))
            ->groupBy('event_name')
            ->get()
            ->each(function ($row) use (&$metrics) {
                $metrics[$row->event_name] = (int) $row->total;
            });

        $urlViews = ProductUrlAttribution::pageViewsByProductId($siteId, $fromDate, $toDate);
        $metrics['product_viewed'] = ProductUrlAttribution::mergeViewCount(
            (int) ($metrics['product_viewed'] ?? 0),
            (int) ($urlViews[$productId] ?? 0)
        );

        $daily = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('product_id', $productId)
            ->whereIn('event_name', ['product_viewed', 'add_to_cart', 'order_placed'])
            ->whereBetween('event_date', [$fromDate, $toDate])
            ->select('event_date', 'event_name', DB::raw('COUNT(*) as total'))
            ->groupBy('event_date', 'event_name')
            ->orderBy('event_date')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->event_date)->toDateString());

        $series = [];
        for ($current = strtotime($fromDate); $current <= strtotime($toDate); $current += 86400) {
            $day = date('Y-m-d', $current);
            $dayRows = $daily->get($day, collect());
            $byName = $dayRows->pluck('total', 'event_name');
            $series[] = [
                'date' => $day,
                'views' => (int) ($byName['product_viewed'] ?? 0),
                'add_to_cart' => (int) ($byName['add_to_cart'] ?? 0),
                'orders' => (int) ($byName['order_placed'] ?? 0),
            ];
        }

        $productSlug = $product->slug ? strtolower($product->slug) : null;
        $topPages = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->whereBetween('event_date', [$fromDate, $toDate])
            ->whereIn('event_name', ['product_viewed', 'page_view'])
            ->whereNotNull('page_url')
            ->where('page_url', '!=', '')
            ->where(function ($q) use ($productId, $productSlug) {
                $q->where('product_id', $productId);
                if ($productSlug) {
                    $q->orWhere('page_url', 'like', '%/product/' . $productSlug . '%');
                }
            })
            ->select('page_url', DB::raw('COUNT(*) as views'))
            ->groupBy('page_url')
            ->orderByDesc('views')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['url' => $this->safePageUrl($r->page_url), 'views' => (int) $r->views])
            ->filter(fn ($row) => $row['url'] !== null)
            ->values()
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

    private function safePageUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $url);
        if ($clean === false) {
            return null;
        }

        return Str::limit($clean, 500, '…');
    }
}
