<?php

namespace App\Analytics\Enterprise\Console;

use App\Analytics\Enterprise\Jobs\GenerateAiInsightsJob;
use App\Analytics\Models\AnalyticsSite;
use Illuminate\Console\Command;

class AnalyticsGenerateInsightsCommand extends Command
{
    protected $signature = 'analytics:generate-insights {--site=}';

    protected $description = 'Generate rule-based AI insights for analytics sites';

    public function handle(): int
    {
        $siteId = $this->option('site');
        $sites = $siteId
            ? AnalyticsSite::query()->where('id', $siteId)->where('is_active', true)->pluck('id')
            : AnalyticsSite::query()->where('is_active', true)->pluck('id');

        foreach ($sites as $id) {
            GenerateAiInsightsJob::dispatch((int) $id);
            $this->info("Queued insights for site {$id}");
        }

        return self::SUCCESS;
    }
}
