<?php

namespace App\Analytics\Enterprise\Services;

use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Support\AnalyticsSchema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BehaviorIngestionService
{
    public function accept(AnalyticsSite $site, array $validated): array
    {
        if (!AnalyticsSchema::hasTable('analytics_behavior_events')) {
            Log::warning('Behavior ingest skipped: run php artisan migrate (analytics enterprise tables missing)', [
                'site_id' => $site->id,
            ]);

            // Accept silently so storefront tracker does not spam 503 until migrate is run
            return ['accepted' => true, 'queued' => 0, 'status' => 202, 'degraded' => true];
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
                'occurred_at' => $this->parseOccurredAt($ev['occurred_at'] ?? null),
                'created_at' => $now->format('Y-m-d H:i:s'),
            ];
        }

        try {
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('analytics_behavior_events')->insert($chunk);
            }

            $this->dispatchOrRun(
                new \App\Analytics\Enterprise\Jobs\AggregateHeatmapJob($site->id)
            );

            if ($this->hasReplayEvents($events)) {
                $this->dispatchOrRun(new \App\Analytics\Enterprise\Jobs\ProcessReplayChunkJob(
                    $site->id,
                    $validated['session_id'],
                    $validated['visitor_id'] ?? null,
                    $events
                ));
            }

            return ['accepted' => true, 'queued' => count($rows), 'status' => 202];
        } catch (Throwable $e) {
            Log::error('Behavior ingest failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return [
                'accepted' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Ingest failed',
                'status' => 503,
            ];
        }
    }

    private function dispatchOrRun(object $job): void
    {
        try {
            $queue = config('analytics_enterprise.behavior_ingest.queue', 'analytics');
            dispatch($job)->onQueue($queue);
        } catch (Throwable $e) {
            Log::warning('Behavior job queue unavailable, running synchronously', [
                'job' => $job::class,
                'error' => $e->getMessage(),
            ]);
            try {
                dispatch_sync($job);
            } catch (Throwable $syncError) {
                Log::error('Behavior job sync run failed', [
                    'job' => $job::class,
                    'error' => $syncError->getMessage(),
                ]);
            }
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

    /** MySQL timestamp columns reject ISO-8601 strings (2026-05-26T10:00:00.000Z). */
    private function parseOccurredAt(mixed $value): string
    {
        try {
            if ($value === null || $value === '') {
                return now()->format('Y-m-d H:i:s');
            }

            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return now()->format('Y-m-d H:i:s');
        }
    }
}
