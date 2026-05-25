<?php

namespace App\Analytics\Console\Commands;

use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Models\AnalyticsWorkspace;
use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AnalyticsInstallSiteCommand extends Command
{
    protected $signature = 'analytics:install-site
        {name : Store display name}
        {domain : Primary domain}
        {--user= : Owner user ID}
        {--origin=* : Allowed CORS origins}';

    protected $description = 'Create analytics workspace and site with API keys';

    public function handle(EloquentAnalyticsSiteRepository $sites): int
    {
        $workspace = AnalyticsWorkspace::query()->firstOrCreate(
            ['slug' => Str::slug($this->argument('name'))],
            [
                'name' => $this->argument('name') . ' Workspace',
                'owner_id' => $this->option('user'),
                'is_active' => true,
            ]
        );

        $publicKey = 'pk_' . Str::random(32);
        $secret = 'sk_' . Str::random(48);

        $site = AnalyticsSite::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $this->argument('name'),
            'domain' => $this->argument('domain'),
            'public_key' => $publicKey,
            'secret_key_hash' => hash('sha256', $secret),
            'allowed_origins' => $this->option('origin') ?: ['*'],
            'is_active' => true,
        ]);

        if ($userId = $this->option('user')) {
            $sites->attachMember($site->id, (int) $userId, 'admin');
        }

        $this->info('Analytics site created.');
        $this->line('Site ID: ' . $site->id);
        $this->line('Public Key (tracker): ' . $publicKey);
        $this->line('Secret Key (server only): ' . $secret);

        return self::SUCCESS;
    }
}
