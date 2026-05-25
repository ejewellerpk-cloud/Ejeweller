<?php

namespace App\Analytics\Services;

use App\Analytics\DTOs\IngestPayloadDTO;
use App\Analytics\Events\AnalyticsRealtimeUpdated;
use App\Analytics\Models\AnalyticsDevice;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Models\AnalyticsVisitor;
use App\Analytics\Contracts\AnalyticsEventRepositoryInterface;
use App\Analytics\Contracts\AnalyticsSessionRepositoryInterface;
use App\Analytics\Support\AnalyticsRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AnalyticsEventProcessingService
{
    public function __construct(
        private readonly AnalyticsEventRepositoryInterface $events,
        private readonly AnalyticsSessionRepositoryInterface $sessions,
        private readonly TrafficAttributionService $attribution,
        private readonly AnalyticsRealtimeService $realtime,
        private readonly BotDetectionService $bots,
    ) {}

    public function process(AnalyticsSite $site, IngestPayloadDTO $payload): int
    {
        if ($this->bots->isBot($payload->context['user_agent'] ?? null)) {
            return 0;
        }

        if (blank($payload->sessionUuid) || blank($payload->visitorUuid)) {
            return 0;
        }

        return DB::transaction(function () use ($site, $payload) {
            $visitor = $this->resolveVisitor($site, $payload);
            $session = $this->resolveSession($site, $payload, $visitor);
            $this->syncDevice($session->id, $payload->context);

            $rows = [];
            $inserted = 0;

            foreach ($payload->events as $event) {
                if (blank($event->eventUuid)) {
                    continue;
                }

                if ($this->isDuplicate($site->id, $event->eventUuid)) {
                    continue;
                }

                $rows[] = $event->toInsertArray($site->id, $session->id, $visitor->id);
                $this->applySideEffects($site->id, $session, $event->eventName, $event->pageUrl);
                $inserted++;
            }

            if (!empty($rows)) {
                $this->events->insertBatch($rows);
                $this->updateSessionMetrics($session, $payload);
            }

            try {
                $this->realtime->touchActiveVisitor($site->id, $payload->sessionUuid);
                event(new AnalyticsRealtimeUpdated($site->id, $this->realtime->snapshot($site->id)));
            } catch (\Throwable) {
                // Realtime counters optional when Redis is not installed
            }

            return $inserted;
        });
    }

    private function isDuplicate(int $siteId, string $eventUuid): bool
    {
        if ($this->events->existsByUuid($siteId, $eventUuid)) {
            return true;
        }

        $cacheKey = 'analytics:dedup:' . $siteId . ':' . $eventUuid;
        $ttl = config('analytics.ingest.dedup_ttl_seconds', 86400);

        if (AnalyticsRedis::available()) {
            return !Redis::set($cacheKey, '1', 'EX', $ttl, 'NX');
        }

        return !Cache::add($cacheKey, '1', now()->addSeconds($ttl));
    }

    private function resolveVisitor(AnalyticsSite $site, IngestPayloadDTO $payload): AnalyticsVisitor
    {
        $attr = $this->attribution->resolve($payload->context);

        $visitor = AnalyticsVisitor::query()->firstOrNew([
            'site_id' => $site->id,
            'visitor_uuid' => $payload->visitorUuid,
        ]);

        if (!$visitor->exists) {
            $visitor->first_source = $attr['source'];
            $visitor->first_medium = $attr['medium'];
            $visitor->first_campaign = $attr['campaign'];
            $visitor->first_seen_at = now();
        }

        if ($payload->userId) {
            $visitor->user_id = $payload->userId;
        }

        $visitor->last_seen_at = now();
        $visitor->session_count = ($visitor->session_count ?? 0) + 1;
        $visitor->save();

        return $visitor;
    }

    private function resolveSession(AnalyticsSite $site, IngestPayloadDTO $payload, AnalyticsVisitor $visitor)
    {
        $attr = $this->attribution->resolve($payload->context);
        $pageUrl = $payload->context['page_url'] ?? null;

        return $this->sessions->upsertSession([
            'site_id' => $site->id,
            'visitor_id' => $visitor->id,
            'session_uuid' => $payload->sessionUuid,
            'user_id' => $payload->userId,
            'landing_page' => $pageUrl,
            'referrer' => $attr['referrer'],
            'source' => $attr['source'],
            'medium' => $attr['medium'],
            'campaign' => $attr['campaign'],
            'content' => $attr['content'],
            'term' => $attr['term'],
            'is_active' => true,
            'started_at' => now(),
        ]);
    }

    private function syncDevice(int $sessionId, array $context): void
    {
        $ua = (string) ($context['user_agent'] ?? '');
        AnalyticsDevice::query()->updateOrCreate(
            ['session_id' => $sessionId],
            [
                'ip_hash' => isset($context['ip']) ? hash('sha256', (string) $context['ip']) : null,
                'country' => $context['country'] ?? null,
                'city' => $context['city'] ?? null,
                'timezone' => $context['timezone'] ?? null,
                'browser' => $context['browser'] ?? null,
                'browser_version' => $context['browser_version'] ?? null,
                'os' => $context['os'] ?? null,
                'device_type' => $context['device_type'] ?? 'desktop',
                'screen' => $context['screen'] ?? null,
                'language' => $context['language'] ?? null,
                'network_type' => $context['network_type'] ?? null,
                'user_agent' => Str::limit($ua, 512, ''),
            ]
        );
    }

    private function updateSessionMetrics($session, IngestPayloadDTO $payload): void
    {
        $pageViews = collect($payload->events)->where('eventName', 'page_view')->count();
        $maxScroll = (int) ($payload->context['scroll_depth'] ?? 0);

        $session->page_views = ($session->page_views ?? 0) + max(1, $pageViews);
        $session->max_scroll_depth = max($session->max_scroll_depth ?? 0, $maxScroll);
        $session->is_bounce = $session->page_views <= 1;
        $session->exit_page = $payload->context['page_url'] ?? $session->exit_page;
        $session->save();
    }

    private function applySideEffects(int $siteId, $session, string $eventName, ?string $pageUrl): void
    {
        if ($eventName === 'page_view') {
            $this->realtime->increment($siteId, 'page_views');
            if ($pageUrl) {
                $path = parse_url($pageUrl, PHP_URL_PATH) ?: '/';
                $this->realtime->recordPage($siteId, $path);
            }
        }

        if (in_array($eventName, ['order_placed', 'order_confirmed'], true)) {
            $this->realtime->increment($siteId, 'orders');
        }

        if ($eventName === 'add_to_cart') {
            $this->realtime->increment($siteId, 'add_to_cart');
        }

        if ($session->source) {
            $this->realtime->recordSource($siteId, $session->source);
        }
    }
}
