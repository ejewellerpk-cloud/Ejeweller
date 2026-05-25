<?php

namespace App\Analytics\Contracts;

interface AnalyticsEventRepositoryInterface
{
    public function insertBatch(array $rows): int;

    public function existsByUuid(int $siteId, string $eventUuid): bool;

    public function topEvents(int $siteId, string $from, string $to, int $limit = 10): array;

    public function countByName(int $siteId, string $eventName, string $from, string $to): int;
}
