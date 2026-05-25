<?php

namespace App\Analytics\Services;

use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Models\AnalyticsSiteMember;
use App\Analytics\Models\AnalyticsWorkspace;
use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Enums\Activity;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class AnalyticsSettingsService
{
    public function __construct(
        private readonly EloquentAnalyticsSiteRepository $sites,
    ) {}

    /**
     * @throws Exception
     */
    public function get(int $userId): array
    {
        $site = $this->ensureValidPublicKey($this->resolveOrCreateSite($userId));

        return $this->formatSite($site);
    }

    /**
     * @throws Exception
     */
    public function update(int $userId, array $data): array
    {
        $site = $this->resolveOrCreateSite($userId);

        if (!empty($data['name'])) {
            $site->name = $data['name'];
        }

        if (!empty($data['domain'])) {
            $site->domain = $this->normalizeDomain($data['domain']);
        }

        if (array_key_exists('allowed_origins', $data)) {
            $site->allowed_origins = $this->parseOrigins($data['allowed_origins']);
        }

        if (!empty($data['public_key']) && $this->isValidPublicKey($data['public_key'])) {
            $exists = AnalyticsSite::query()
                ->where('public_key', $data['public_key'])
                ->where('id', '!=', $site->id)
                ->exists();
            if ($exists) {
                throw new Exception('This public key is already used by another site.');
            }
            $site->public_key = $data['public_key'];
        }

        $enabled = (int) ($data['analytics_enabled'] ?? Activity::ENABLE);
        $site->is_active = $enabled === Activity::ENABLE;
        $site->save();

        $this->sites->attachMember($site->id, $userId, 'admin');
        $this->syncEnv($site, $enabled);

        return $this->formatSite($this->ensureValidPublicKey($site->fresh()));
    }

    /**
     * @throws Exception
     */
    public function regenerateKeys(int $userId): array
    {
        $site = $this->resolveOrCreateSite($userId);

        $publicKey = 'pk_' . Str::random(32);
        $secret = 'sk_' . Str::random(48);

        $site->public_key = $publicKey;
        $site->secret_key_hash = hash('sha256', $secret);
        $site->save();

        $this->sites->attachMember($site->id, $userId, 'admin');
        $this->syncEnv($site, $site->is_active ? Activity::ENABLE : Activity::DISABLE);

        $formatted = $this->formatSite($site);
        $formatted['secret_key'] = $secret;
        $formatted['secret_key_notice'] = 'Copy the secret key now. It will not be shown again.';

        return $formatted;
    }

    public function resolveOrCreateSite(int $userId): AnalyticsSite
    {
        try {
            return $this->resolveOrCreateSiteInternal($userId);
        } catch (QueryException $e) {
            throw new Exception(
                'Analytics database tables are missing. Run: php artisan migrate',
                0,
                $e
            );
        }
    }

    private function resolveOrCreateSiteInternal(int $userId): AnalyticsSite
    {
        $memberSiteId = AnalyticsSiteMember::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->value('site_id');

        if ($memberSiteId) {
            $memberSite = AnalyticsSite::query()
                ->where('id', $memberSiteId)
                ->where('is_active', true)
                ->first();
            if ($memberSite) {
                $this->sites->attachMember($memberSite->id, $userId, 'admin');

                return $this->ensureValidPublicKey($memberSite);
            }
        }

        $existing = AnalyticsSite::query()
            ->where('is_active', true)
            ->whereHas('workspace', fn ($w) => $w->where('owner_id', $userId))
            ->orderBy('id')
            ->first();

        if ($existing) {
            $this->sites->attachMember($existing->id, $userId, 'admin');

            return $this->ensureValidPublicKey($existing);
        }

        $fallback = AnalyticsSite::query()->where('is_active', true)->orderBy('id')->first();
        if ($fallback) {
            $this->sites->attachMember($fallback->id, $userId, 'admin');

            return $this->ensureValidPublicKey($fallback);
        }

        $appName = config('app.name', 'Store');
        $domain = $this->normalizeDomain(parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');

        $workspace = AnalyticsWorkspace::query()->firstOrCreate(
            ['slug' => Str::slug($appName)],
            [
                'name' => $appName . ' Workspace',
                'owner_id' => $userId,
                'is_active' => true,
            ]
        );

        if (!$workspace->owner_id) {
            $workspace->owner_id = $userId;
            $workspace->save();
        }

        $publicKey = 'pk_' . Str::random(32);
        $secret = 'sk_' . Str::random(48);

        $site = AnalyticsSite::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $appName,
            'domain' => $domain,
            'public_key' => $publicKey,
            'secret_key_hash' => hash('sha256', $secret),
            'allowed_origins' => ['*'],
            'is_active' => true,
        ]);

        $this->sites->attachMember($site->id, $userId, 'admin');

        return $this->ensureValidPublicKey($site);
    }

    private function isValidPublicKey(?string $key): bool
    {
        if (!is_string($key) || !str_starts_with($key, 'pk_')) {
            return false;
        }

        return strlen($key) >= 20 && !str_contains($key, '...');
    }

    /**
     * Fix placeholder keys (e.g. "pk_...") saved by mistake — common cause of collect 401.
     */
    private function ensureValidPublicKey(AnalyticsSite $site): AnalyticsSite
    {
        if ($this->isValidPublicKey($site->public_key)) {
            return $site;
        }

        $envKey = trim((string) env('ANALYTICS_PUBLIC_KEY', ''));
        if ($this->isValidPublicKey($envKey)) {
            $site->public_key = $envKey;
            $site->save();

            return $site->fresh();
        }

        $site->public_key = 'pk_' . Str::random(32);
        $site->save();

        return $site->fresh();
    }

    /** Optional .env mirror — off by default (see ANALYTICS_SYNC_ENV). */
    private function syncEnv(AnalyticsSite $site, int $enabled): void
    {
        if (!$this->shouldSyncEnv()) {
            return;
        }

        if (!app()->bound(\Dipokhalder\EnvEditor\EnvEditor::class)) {
            return;
        }

        try {
            app(\Dipokhalder\EnvEditor\EnvEditor::class)->addData([
                'ANALYTICS_ENABLED' => $enabled === Activity::ENABLE ? 'true' : 'false',
                'ANALYTICS_PUBLIC_KEY' => $site->public_key,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Analytics .env sync skipped: ' . $e->getMessage());
        }
    }

    private function shouldSyncEnv(): bool
    {
        if (!config('analytics.sync_env', false)) {
            return false;
        }

        $url = strtolower((string) config('app.url', ''));

        return !str_contains($url, '127.0.0.1')
            && !str_contains($url, 'localhost');
    }

    private function formatSite(AnalyticsSite $site): array
    {
        $origins = $site->allowed_origins ?? ['*'];

        return [
            'site_id' => $site->id,
            'name' => $site->name,
            'domain' => $site->domain,
            'public_key' => $site->public_key,
            'analytics_enabled' => $site->is_active ? Activity::ENABLE : Activity::DISABLE,
            'allowed_origins' => is_array($origins) ? implode(', ', $origins) : (string) $origins,
            'tracker_url' => asset(config('analytics.tracker.cdn_url', '/analytics/tracker.js')),
            'collect_url' => config('analytics.tracker.collect_url') ?: url('/api/analytics/v1/collect'),
            'has_secret_key' => !blank($site->secret_key_hash),
        ];
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain ?: 'localhost';
    }

    private function parseOrigins(array|string|null $raw): array
    {
        if (is_array($raw)) {
            $parts = array_filter(array_map('trim', $raw));

            return $parts ?: ['*'];
        }

        $parts = array_filter(array_map('trim', explode(',', (string) $raw)));

        return $parts ?: ['*'];
    }
}
