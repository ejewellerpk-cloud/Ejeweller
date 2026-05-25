<?php

namespace App\Analytics\Repositories;

use App\Analytics\Contracts\AnalyticsSiteRepositoryInterface;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Models\AnalyticsSiteMember;

class EloquentAnalyticsSiteRepository implements AnalyticsSiteRepositoryInterface
{
    public function findByPublicKey(string $publicKey): ?AnalyticsSite
    {
        $publicKey = trim($publicKey);
        if ($publicKey === '') {
            return null;
        }

        return AnalyticsSite::query()
            ->where('public_key', $publicKey)
            ->first();
    }

    public function findForUser(int $siteId, int $userId): ?AnalyticsSite
    {
        if ($siteId <= 0) {
            return null;
        }

        $site = AnalyticsSite::query()
            ->where('id', $siteId)
            ->where(function ($q) use ($userId) {
                $q->whereHas('members', fn ($m) => $m->where('user_id', $userId))
                    ->orWhereHas('workspace', fn ($w) => $w->where('owner_id', $userId));
            })
            ->first();

        if ($site) {
            return $site;
        }

        if (AnalyticsSiteMember::query()->where('user_id', $userId)->where('site_id', $siteId)->exists()) {
            return AnalyticsSite::query()->find($siteId);
        }

        return null;
    }

    public function listForUser(int $userId): array
    {
        $sites = AnalyticsSite::query()
            ->where(function ($q) use ($userId) {
                $q->whereHas('members', fn ($m) => $m->where('user_id', $userId))
                    ->orWhereHas('workspace', fn ($w) => $w->where('owner_id', $userId));
            })
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get();

        if ($sites->isNotEmpty()) {
            return $sites->all();
        }

        $memberSiteIds = AnalyticsSiteMember::query()
            ->where('user_id', $userId)
            ->pluck('site_id');

        if ($memberSiteIds->isNotEmpty()) {
            return AnalyticsSite::query()
                ->whereIn('id', $memberSiteIds)
                ->orderByDesc('updated_at')
                ->get()
                ->all();
        }

        return AnalyticsSite::query()
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->all();
    }

    public function attachMember(int $siteId, int $userId, string $role = 'admin'): void
    {
        AnalyticsSiteMember::query()->updateOrCreate(
            ['site_id' => $siteId, 'user_id' => $userId],
            ['role' => $role]
        );
    }
}
