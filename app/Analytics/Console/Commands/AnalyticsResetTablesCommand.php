<?php

namespace App\Analytics\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsResetTablesCommand extends Command
{
    protected $signature = 'analytics:reset-tables {--force : Skip confirmation}';

    protected $description = 'Drop all analytics platform tables (use before a clean re-migrate)';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Drop ALL analytics_* tables?')) {
            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        $tables = [
            'analytics_replay_chunks',
            'analytics_heatmap_points',
            'analytics_funnel_definitions',
            'analytics_daily_metrics',
            'analytics_event_failures',
            'analytics_events',
            'analytics_devices',
            'analytics_sessions',
            'analytics_visitors',
            'analytics_site_members',
            'analytics_sites',
            'analytics_workspaces',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->line("Dropped {$table}");
            }
        }

        DB::table('migrations')
            ->where('migration', 'like', '%analytics_platform%')
            ->delete();

        Schema::enableForeignKeyConstraints();

        $this->info('Done. Run: php artisan migrate');

        return self::SUCCESS;
    }
}
