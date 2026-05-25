<?php

namespace App\Analytics\Jobs;

use App\Analytics\Models\AnalyticsDailyMetric;
use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Models\AnalyticsSession;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Models\AnalyticsVisitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateAnalyticsDailyMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $siteId,
        public ?string $date = null,
    ) {}

    public function handle(): void
    {
        $date = $this->date ?? now()->subDay()->toDateString();
        $site = AnalyticsSite::query()->find($this->siteId);
        if (!$site) {
            return;
        }

        $bySource = AnalyticsSession::query()
            ->select('source', DB::raw('COUNT(*) as total'))
            ->where('site_id', $site->id)
            ->whereDate('started_at', $date)
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        AnalyticsDailyMetric::query()->updateOrCreate(
            ['site_id' => $site->id, 'metric_date' => $date],
            [
                'visitors' => AnalyticsVisitor::query()
                    ->where('site_id', $site->id)->whereDate('last_seen_at', $date)->count(),
                'sessions' => AnalyticsSession::query()
                    ->where('site_id', $site->id)->whereDate('started_at', $date)->count(),
                'page_views' => AnalyticsEvent::query()
                    ->where('site_id', $site->id)->where('event_name', 'page_view')
                    ->whereDate('event_date', $date)->count(),
                'orders' => AnalyticsEvent::query()
                    ->where('site_id', $site->id)->where('event_name', 'order_placed')
                    ->whereDate('event_date', $date)->count(),
                'revenue' => (float) AnalyticsEvent::query()
                    ->where('site_id', $site->id)->whereIn('event_name', ['order_placed', 'order_confirmed'])
                    ->whereDate('event_date', $date)->sum('revenue'),
                'bounces' => AnalyticsSession::query()
                    ->where('site_id', $site->id)->whereDate('started_at', $date)->where('is_bounce', true)->count(),
                'add_to_carts' => AnalyticsEvent::query()
                    ->where('site_id', $site->id)->where('event_name', 'add_to_cart')
                    ->whereDate('event_date', $date)->count(),
                'checkouts_started' => AnalyticsEvent::query()
                    ->where('site_id', $site->id)->where('event_name', 'checkout_started')
                    ->whereDate('event_date', $date)->count(),
                'checkouts_abandoned' => AnalyticsEvent::query()
                    ->where('site_id', $site->id)->where('event_name', 'checkout_abandoned')
                    ->whereDate('event_date', $date)->count(),
                'by_source' => $bySource,
            ]
        );
    }
}
