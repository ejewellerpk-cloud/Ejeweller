<?php

namespace App\Analytics\Console\Commands;

use App\Analytics\Jobs\AggregateAnalyticsDailyMetricsJob;
use App\Analytics\Models\AnalyticsSite;
use Illuminate\Console\Command;

class AnalyticsAggregateDailyCommand extends Command
{
    protected $signature = 'analytics:aggregate-daily {--date=}';

    protected $description = 'Aggregate daily analytics metrics for all sites';

    public function handle(): int
    {
        $date = $this->option('date');

        AnalyticsSite::query()->where('is_active', true)->each(function ($site) use ($date) {
            AggregateAnalyticsDailyMetricsJob::dispatch($site->id, $date)
                ->onQueue(config('analytics.ingest.queue', 'analytics'));
        });

        $this->info('Daily aggregation jobs dispatched.');

        return self::SUCCESS;
    }
}
