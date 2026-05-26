<?php

namespace App\Analytics\Enterprise\Services;

use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Support\AnalyticsSchema;
use Illuminate\Support\Facades\DB;

class JourneyService
{
    public function forVisitor(int $siteId, string $visitorUuid, int $limit = 100): array
    {
        return $this->buildTimeline($siteId, visitorUuid: $visitorUuid, limit: $limit);
    }

    public function forCustomer(int $siteId, int $userId, int $limit = 100): array
    {
        return $this->buildTimeline($siteId, userId: $userId, limit: $limit);
    }

    private function buildTimeline(int $siteId, ?string $visitorUuid = null, ?int $userId = null, int $limit = 100): array
    {
        if (!AnalyticsSchema::hasTable('analytics_events')) {
            return ['entries' => [], 'merged' => false];
        }

        $visitorIds = [];
        if ($visitorUuid) {
            $visitorIds[] = DB::table('analytics_visitors')
                ->where('site_id', $siteId)
                ->where('visitor_uuid', $visitorUuid)
                ->value('id');
        }
        if ($userId) {
            $visitorIds = array_merge($visitorIds, DB::table('analytics_visitors')
                ->where('site_id', $siteId)
                ->where('user_id', $userId)
                ->pluck('id')
                ->all());
        }
        $visitorIds = array_filter(array_unique($visitorIds));

        $q = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->orderByDesc('occurred_at')
            ->limit($limit);

        if ($visitorIds !== []) {
            $q->whereIn('visitor_id', $visitorIds);
        } elseif ($visitorUuid || $userId) {
            return ['entries' => [], 'merged' => (bool) $userId];
        }

        $events = $q->get(['event_name', 'occurred_at', 'page_url', 'product_id', 'revenue', 'properties']);

        $entries = $events->map(fn ($e) => [
            'type' => $e->event_name,
            'label' => $this->labelForEvent($e->event_name, $e),
            'occurred_at' => $e->occurred_at?->toIso8601String(),
            'meta' => [
                'page_url' => $e->page_url,
                'product_id' => $e->product_id,
                'revenue' => $e->revenue,
            ],
        ])->values()->all();

        if (AnalyticsSchema::hasTable('analytics_journey_entries')) {
            $this->materialize($siteId, $visitorUuid, $userId, $entries);
        }

        return [
            'entries' => array_reverse($entries),
            'merged' => (bool) $userId,
        ];
    }

    private function labelForEvent(string $name, $event): string
    {
        return match ($name) {
            'page_view' => 'Visited page',
            'product_viewed' => 'Viewed product #' . ($event->product_id ?? '?'),
            'add_to_cart' => 'Added to cart',
            'remove_from_cart' => 'Removed from cart',
            'checkout_started' => 'Started checkout',
            'checkout_abandoned' => 'Abandoned checkout',
            'order_placed', 'order_confirmed' => 'Placed order',
            'search_performed' => 'Search',
            default => str_replace('_', ' ', $name),
        };
    }

    private function materialize(int $siteId, ?string $visitorUuid, ?int $userId, array $entries): void
    {
        foreach (array_slice($entries, 0, 20) as $entry) {
            DB::table('analytics_journey_entries')->insertOrIgnore([
                'site_id' => $siteId,
                'visitor_uuid' => $visitorUuid,
                'user_id' => $userId,
                'entry_type' => $entry['type'],
                'label' => $entry['label'],
                'meta' => json_encode($entry['meta'] ?? []),
                'occurred_at' => $entry['occurred_at'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
