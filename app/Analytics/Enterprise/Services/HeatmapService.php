<?php

namespace App\Analytics\Enterprise\Services;

use App\Analytics\Support\AnalyticsSchema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HeatmapService
{
    public function pages(int $siteId, string $from, string $to, ?string $device = null): array
    {
        if (!AnalyticsSchema::hasTable('analytics_heatmap_buckets')) {
            return [];
        }

        $q = DB::table('analytics_heatmap_buckets')
            ->where('site_id', $siteId)
            ->whereBetween('bucket_date', [$from, $to])
            ->select('page_path', DB::raw('SUM(count) as total'))
            ->groupBy('page_path')
            ->orderByDesc('total')
            ->limit(100);

        if ($device && $device !== 'all') {
            $q->whereIn('device_type', [$device, 'all']);
        }

        return $q->get()->map(fn ($r) => [
            'page_path' => $r->page_path,
            'interactions' => (int) $r->total,
        ])->all();
    }

    public function snapshot(int $siteId, string $pagePath, string $from, string $to, string $type = 'click', ?string $device = null): array
    {
        if (!AnalyticsSchema::hasTable('analytics_heatmap_buckets')) {
            return ['grid_size' => config('analytics_enterprise.heatmap.grid_size', 32), 'cells' => []];
        }

        $grid = (int) config('analytics_enterprise.heatmap.grid_size', 32);
        $q = DB::table('analytics_heatmap_buckets')
            ->where('site_id', $siteId)
            ->where('page_path', $pagePath)
            ->where('heatmap_type', $type)
            ->whereBetween('bucket_date', [$from, $to]);

        if ($device && $device !== 'all') {
            $q->whereIn('device_type', [$device, 'all']);
        }

        $cells = $q
            ->groupBy('grid_x', 'grid_y')
            ->get(['grid_x', 'grid_y', DB::raw('SUM(count) as count')])
            ->map(fn ($r) => [
                'x' => (int) $r->grid_x,
                'y' => (int) $r->grid_y,
                'count' => (int) $r->count,
            ])
            ->all();

        return ['grid_size' => $grid, 'page_path' => $pagePath, 'type' => $type, 'cells' => $cells];
    }

    public function aggregateSite(int $siteId, ?string $date = null): int
    {
        if (!AnalyticsSchema::hasTable('analytics_behavior_events')) {
            return 0;
        }

        $bucketDate = $date ?? Carbon::today()->toDateString();
        $grid = (int) config('analytics_enterprise.heatmap.grid_size', 32);
        $rows = DB::table('analytics_behavior_events')
            ->where('site_id', $siteId)
            ->whereDate('occurred_at', $bucketDate)
            ->whereIn('event_type', ['click', 'rage_click', 'dead_click', 'scroll_depth'])
            ->get();

        $upserts = [];
        foreach ($rows as $row) {
            $payload = json_decode($row->payload, true) ?: [];
            $type = $row->event_type === 'scroll_depth' ? 'scroll' : 'click';
            $gx = (int) floor(((float) ($payload['x_pct'] ?? 0)) / (100 / $grid));
            $gy = (int) floor(((float) ($payload['y_pct'] ?? ($payload['depth'] ?? 0))) / (100 / $grid));
            $gx = max(0, min($grid - 1, $gx));
            $gy = max(0, min($grid - 1, $gy));
            $key = implode('|', [$row->page_path, $type, $row->device_type ?? 'all', $gx, $gy]);
            $upserts[$key] = ($upserts[$key] ?? 0) + 1;
        }

        $now = now();
        foreach ($upserts as $key => $count) {
            [$path, $type, $device, $gx, $gy] = explode('|', $key);
            $match = [
                'site_id' => $siteId,
                'bucket_date' => $bucketDate,
                'page_path' => $path,
                'heatmap_type' => $type,
                'device_type' => $device,
                'grid_x' => (int) $gx,
                'grid_y' => (int) $gy,
            ];
            $existing = DB::table('analytics_heatmap_buckets')->where($match)->first();
            if ($existing) {
                DB::table('analytics_heatmap_buckets')->where('id', $existing->id)->update([
                    'count' => $existing->count + $count,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('analytics_heatmap_buckets')->insert(array_merge($match, [
                    'count' => $count,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        return count($upserts);
    }
}
