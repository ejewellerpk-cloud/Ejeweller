<?php

namespace App\Analytics\Contracts;

use App\Analytics\Models\AnalyticsSite;

interface AnalyticsSiteRepositoryInterface
{
    public function findByPublicKey(string $publicKey): ?AnalyticsSite;

    public function findForUser(int $siteId, int $userId): ?AnalyticsSite;
}
