<?php

namespace App\Analytics\Services;

use App\Analytics\DTOs\IngestPayloadDTO;
use App\Analytics\Jobs\ProcessAnalyticsIngestJob;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Contracts\AnalyticsSiteRepositoryInterface;

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

        if (!$this->buffer->isAvailable()) {
            $this->processor->process($site, $payload);

            return [
                'accepted' => true,
                'queued' => count($payload->events),
                'status' => 202,
            ];
        }

        $envelope = [
            'site_id' => $site->id,
            'body' => $body,
            'received_at' => now()->toIso8601String(),
        ];

        $this->buffer->push($site->id, $envelope);

        ProcessAnalyticsIngestJob::dispatch($site->id)
            ->onQueue(config('analytics.ingest.queue', 'analytics'));

        return [
            'accepted' => true,
            'queued' => count($payload->events),
            'status' => 202,
        ];
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
            $processed += $this->processor->process($site, $dto);
        }

        return $processed;
    }
}
