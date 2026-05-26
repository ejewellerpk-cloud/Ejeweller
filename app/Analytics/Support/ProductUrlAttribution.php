<?php

namespace App\Analytics\Support;

use App\Analytics\Models\AnalyticsEvent;
use App\Enums\Status;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductUrlAttribution
{
    /** @return array<string, int> lowercase slug => product id */
    public static function slugToIdMap(): array
    {
        try {
            return Cache::remember('analytics:product_slug_map', 300, function () {
                $map = [];
                Product::query()
                    ->where('status', Status::ACTIVE)
                    ->pluck('id', 'slug')
                    ->each(function ($id, $slug) use (&$map) {
                        if (is_string($slug) && $slug !== '') {
                            $map[strtolower($slug)] = (int) $id;
                        }
                    });

                return $map;
            });
        } catch (\Throwable) {
            return [];
        }
    }

    public static function slugFromProductUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        if (!preg_match('#/product/([^/?#]+)/?$#i', $path, $matches)) {
            return null;
        }

        return strtolower(urldecode($matches[1]));
    }

    public static function productIdFromUrl(?string $url, ?array $slugMap = null): ?int
    {
        $slug = self::slugFromProductUrl($url);
        if ($slug === null) {
            return null;
        }

        $map = $slugMap ?? self::slugToIdMap();

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        if (ctype_digit($slug)) {
            return (int) $slug;
        }

        return null;
    }

    /**
     * Page views on /product/* URLs attributed by product id (page_view only).
     *
     * @return array<int, int>
     */
    public static function pageViewsByProductId(int $siteId, string $fromDate, string $toDate): array
    {
        if (!AnalyticsSchema::hasEventsTable()) {
            return [];
        }

        $slugMap = self::slugToIdMap();
        $counts = [];

        AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->whereBetween('event_date', [$fromDate, $toDate])
            ->where('event_name', 'page_view')
            ->where('page_url', 'like', '%/product/%')
            ->select('page_url', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('page_url')
            ->get()
            ->each(function ($row) use ($slugMap, &$counts) {
                $pid = self::productIdFromUrl($row->page_url, $slugMap);
                if ($pid) {
                    $counts[$pid] = ($counts[$pid] ?? 0) + (int) $row->total;
                }
            });

        return $counts;
    }

    /**
     * @return Collection<int, Collection<int, object{event_name: string, total: int}>>
     */
    public static function eventsGroupedByProductId(
        int $siteId,
        string $fromDate,
        string $toDate,
        array $eventNames
    ): Collection {
        if (!AnalyticsSchema::hasEventsTable()) {
            return collect();
        }

        $grouped = collect();

        AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->whereBetween('event_date', [$fromDate, $toDate])
            ->whereIn('event_name', $eventNames)
            ->whereNotNull('product_id')
            ->select('product_id', 'event_name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('product_id', 'event_name')
            ->get()
            ->each(function ($row) use ($grouped) {
                $pid = (int) $row->product_id;
                if (!$grouped->has($pid)) {
                    $grouped->put($pid, collect());
                }
                $grouped->get($pid)->push($row);
            });

        return $grouped;
    }

    public static function mergeViewCount(int $trackedViews, int $urlPageViews): int
    {
        return max($trackedViews, $urlPageViews);
    }
}
