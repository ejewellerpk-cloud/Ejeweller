<?php

namespace App\Analytics\Enterprise\Console;

use App\Analytics\Support\AnalyticsSchema;
use Illuminate\Console\Command;

class AnalyticsEnsureEnterpriseCommand extends Command
{
    protected $signature = 'analytics:ensure-enterprise {--fix : Create any missing enterprise tables}';

    protected $description = 'Check (and optionally create) analytics enterprise intelligence tables';

    private const TABLES = [
        'analytics_behavior_events',
        'analytics_heatmap_buckets',
        'analytics_replay_recordings',
        'analytics_replay_chunks',
        'analytics_ai_insights',
        'analytics_recovery_campaigns',
        'analytics_recovery_queue',
        'analytics_journey_entries',
        'analytics_customer_scores',
        'analytics_experiments',
        'analytics_experiment_assignments',
        'analytics_attribution_touches',
        'analytics_campaign_costs',
        'analytics_alert_rules',
        'analytics_alert_incidents',
        'analytics_saas_plans',
        'analytics_saas_subscriptions',
    ];

    public function handle(): int
    {
        $missing = [];
        foreach (self::TABLES as $table) {
            $ok = AnalyticsSchema::hasTable($table);
            $this->line(sprintf('  [%s] %s', $ok ? 'OK' : 'MISSING', $table));
            if (!$ok) {
                $missing[] = $table;
            }
        }

        if ($missing === []) {
            $this->info('All enterprise tables exist.');

            return self::SUCCESS;
        }

        $this->warn(count($missing) . ' table(s) missing.');

        if (!$this->option('fix')) {
            $this->newLine();
            $this->comment('Migration may show "Nothing to migrate" while tables are missing (failed run or wrong DB).');
            $this->comment('Run: php artisan analytics:ensure-enterprise --fix');
            $this->comment('Or rollback + re-run:');
            $this->comment('  php artisan migrate:rollback --path=database/migrations/2026_05_27_100000_create_analytics_enterprise_tables.php');
            $this->comment('  php artisan migrate --path=database/migrations/2026_05_27_100000_create_analytics_enterprise_tables.php --force');

            return self::FAILURE;
        }

        if (!AnalyticsSchema::hasTable('analytics_sites')) {
            $this->error('Base analytics tables missing. Run: php artisan migrate --force');

            return self::FAILURE;
        }

        $path = database_path('migrations/2026_05_27_100000_create_analytics_enterprise_tables.php');
        if (!is_file($path)) {
            $this->error('Migration file not found: ' . $path);

            return self::FAILURE;
        }

        $this->info('Running enterprise migration up() for missing tables…');
        $migration = require $path;
        $migration->up();

        $stillMissing = array_filter(self::TABLES, fn ($t) => !AnalyticsSchema::hasTable($t));
        if ($stillMissing !== []) {
            $this->error('Still missing: ' . implode(', ', $stillMissing));

            return self::FAILURE;
        }

        $this->info('Enterprise tables are ready.');

        return self::SUCCESS;
    }
}
