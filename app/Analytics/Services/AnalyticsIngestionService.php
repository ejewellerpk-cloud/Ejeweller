<?php

namespace App\Analytics\Services;

use App\Analytics\DTOs\IngestPayloadDTO;
use App\Analytics\Jobs\ProcessAnalyticsIngestJob;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Contracts\AnalyticsSiteRepositoryInterface;
use Illuminate\Support\Facades\Log;

class AnalyticsIngestionService
{
    public function __construct(
        private readonly AnalyticsSiteRepositoryInterface $sites,
        private readonly AnalyticsIngestionBufferService $buffer,
        private readonly AnalyticsEventProcessingService $processor,
    ) {}

    public function accept(AnalyticsSite $site, array $body, ?string $origin = null): array
    {
        if (!$site->isOriginAllowed($origin)) {
            return ['accepted' => false, 'message' => 'Origin not allowed', 'status' => 403];
        }

        $events = $body['events'] ?? [];
        $maxBatch = config('analytics.ingest.max_batch_size', 50);
        if (count($events) > $maxBatch) {
            return ['accepted' => false, 'message' => 'Batch too large', 'status' => 422];
        }

        $payload = IngestPayloadDTO::fromArray($body);
        if (empty($payload->events)) {
            return ['accepted' => true, 'queued' => 0, 'status' => 202];
        }

        if ($this->buffer->isAvailable() && $this->tryQueuedIngest($site, $body, $payload)) {
            return [
                'accepted' => true,
                'queued' => count($payload->events),
                'status' => 202,
            ];
        }

        return $this->processSynchronously($site, $payload);
    }

    private function tryQueuedIngest(AnalyticsSite $site, array $body, IngestPayloadDTO $payload): bool
    {
        try {
            $envelope = [
                'site_id' => $site->id,
                'body' => $body,
                'received_at' => now()->toIso8601String(),
            ];

            $this->buffer->push($site->id, $envelope);

            $queue = config('analytics.ingest.queue', 'default');
            ProcessAnalyticsIngestJob::dispatch($site->id)->onQueue($queue);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Analytics queued ingest unavailable, using sync fallback', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function processSynchronously(AnalyticsSite $site, IngestPayloadDTO $payload): array
    {
        try {
            $count = $this->processor->process($site, $payload);

            return [
                'accepted' => true,
                'queued' => $count,
                'status' => 202,
            ];
        } catch (\Throwable $e) {
            Log::error('Analytics sync ingest failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Unable to store analytics events';

            return [
                'accepted' => false,
                'message' => $message,
                'status' => 503,
            ];
        }
    }

    public function drainSite(int $siteId): int
    {
        $site = AnalyticsSite::query()->find($siteId);
        if (!$site) {
            return 0;
        }

        $processed = 0;
        $batches = $this->buffer->popBatch($siteId, 50);

        foreach ($batches as $envelope) {
            $body = $envelope['body'] ?? [];
            $dto = IngestPayloadDTO::fromArray($body);
            try {
                $processed += $this->processor->process($site, $dto);
            } catch (\Throwable $e) {
                Log::error('Analytics drain batch failed', [
                    'site_id' => $siteId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }
}
