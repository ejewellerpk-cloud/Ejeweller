<?php

namespace App\Analytics\Contracts;

use App\Analytics\Models\AnalyticsSession;

interface AnalyticsSessionRepositoryInterface
{
    public function findByUuid(int $siteId, string $sessionUuid): ?AnalyticsSession;

    public function upsertSession(array $attributes): AnalyticsSession;

    public function activeCount(int $siteId, int $windowSeconds): int;
}
