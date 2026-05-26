<?php

namespace App\Analytics\Enterprise\Services;

use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Support\AnalyticsSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BehaviorIngestionService
{
    public function accept(AnalyticsSite $site, array $validated): array
    {
        if (!AnalyticsSchema::hasTable('analytics_behavior_events')) {
            return ['accepted' => false, 'message' => 'Enterprise tables not migrated', 'status' => 503];
        }

        $events = $validated['events'] ?? [];
        if ($events === []) {
            return ['accepted' => true, 'queued' => 0, 'status' => 202];
        }

        $rows = [];
        $now = now();
        foreach ($events as $ev) {
            $rows[] = [
                'site_id' => $site->id,
                'session_uuid' => $validated['session_id'],
                'visitor_uuid' => $validated['visitor_id'] ?? null,
                'event_type' => $ev['type'],
                'page_path' => mb_substr($ev['page_path'] ?? '/', 0, 512),
                'viewport_w' => (int) ($ev['viewport_w'] ?? 0),
                'viewport_h' => (int) ($ev['viewport_h'] ?? 0),
                'device_type' => $ev['device_type'] ?? null,
                'payload' => json_encode($ev['data'] ?? []),
                'occurred_at' => $ev['occurred_at'] ?? $now,
                'created_at' => $now,
            ];
        }

        try {
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('analytics_behavior_events')->insert($chunk);
            }

            dispatch(new \App\Analytics\Enterprise\Jobs\AggregateHeatmapJob($site->id))
                ->onQueue(config('analytics_enterprise.behavior_ingest.queue', 'analytics'));

            if ($this->hasReplayEvents($events)) {
                dispatch(new \App\Analytics\Enterprise\Jobs\ProcessReplayChunkJob(
                    $site->id,
                    $validated['session_id'],
                    $validated['visitor_id'] ?? null,
                    $events
                ))->onQueue(config('analytics_enterprise.behavior_ingest.queue', 'analytics'));
            }

            return ['accepted' => true, 'queued' => count($rows), 'status' => 202];
        } catch (Throwable $e) {
            Log::error('Behavior ingest failed', ['site_id' => $site->id, 'error' => $e->getMessage()]);

            return ['accepted' => false, 'message' => 'Ingest failed', 'status' => 503];
        }
    }

    private function hasReplayEvents(array $events): bool
    {
        foreach ($events as $ev) {
            if (str_starts_with((string) ($ev['type'] ?? ''), 'replay_')) {
                return true;
            }
        }

        return false;
    }
}
