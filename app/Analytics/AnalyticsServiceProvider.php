<?php

namespace App\Analytics;

use App\Analytics\Console\Commands\AnalyticsAggregateDailyCommand;
use App\Analytics\Console\Commands\AnalyticsInstallSiteCommand;
use App\Analytics\Console\Commands\AnalyticsResetTablesCommand;
use App\Analytics\Enterprise\Console\AnalyticsEnsureEnterpriseCommand;
use App\Analytics\Enterprise\Console\AnalyticsGenerateInsightsCommand;
use App\Analytics\Contracts\AnalyticsEventRepositoryInterface;
use App\Analytics\Contracts\AnalyticsSessionRepositoryInterface;
use App\Analytics\Contracts\AnalyticsSiteRepositoryInterface;
use App\Analytics\Repositories\EloquentAnalyticsEventRepository;
use App\Analytics\Repositories\EloquentAnalyticsSessionRepository;
use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/analytics.php', 'analytics');
        $this->mergeConfigFrom(__DIR__ . '/../../config/analytics_enterprise.php', 'analytics_enterprise');

        $this->app->bind(AnalyticsSiteRepositoryInterface::class, EloquentAnalyticsSiteRepository::class);
        $this->app->bind(AnalyticsEventRepositoryInterface::class, EloquentAnalyticsEventRepository::class);
        $this->app->bind(AnalyticsSessionRepositoryInterface::class, EloquentAnalyticsSessionRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyticsInstallSiteCommand::class,
                AnalyticsAggregateDailyCommand::class,
                AnalyticsResetTablesCommand::class,
                AnalyticsGenerateInsightsCommand::class,
                AnalyticsEnsureEnterpriseCommand::class,
            ]);
        }

        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('analytics:aggregate-daily')->dailyAt('01:15');
                $schedule->command('analytics:generate-insights')
                    ->cron(config('analytics_enterprise.insights.schedule', '0 */6 * * *'))
                    ->when(fn () => config('analytics_enterprise.features.ai_insights', true));
            }
        });
    }
}
