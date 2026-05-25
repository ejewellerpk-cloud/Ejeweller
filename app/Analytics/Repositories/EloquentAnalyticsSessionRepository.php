<?php

namespace App\Analytics\Repositories;

use App\Analytics\Contracts\AnalyticsSessionRepositoryInterface;
use App\Analytics\Models\AnalyticsSession;
use Carbon\Carbon;

class EloquentAnalyticsSessionRepository implements AnalyticsSessionRepositoryInterface
{
    public function findByUuid(int $siteId, string $sessionUuid): ?AnalyticsSession
    {
        return AnalyticsSession::query()
            ->where('site_id', $siteId)
            ->where('session_uuid', $sessionUuid)
            ->first();
    }

    public function upsertSession(array $attributes): AnalyticsSession
    {
        return AnalyticsSession::query()->updateOrCreate(
            [
                'site_id' => $attributes['site_id'],
                'session_uuid' => $attributes['session_uuid'],
            ],
            $attributes
        );
    }

    public function activeCount(int $siteId, int $windowSeconds): int
    {
        $since = Carbon::now()->subSeconds($windowSeconds);

        return AnalyticsSession::query()
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->where('started_at', '>=', $since)
            ->count();
    }

    public function markInactive(int $siteId, string $sessionUuid): void
    {
        AnalyticsSession::query()
            ->where('site_id', $siteId)
            ->where('session_uuid', $sessionUuid)
            ->update(['is_active' => false, 'ended_at' => now()]);
    }
}
