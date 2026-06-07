<?php

namespace App\Analytics\Enterprise\Jobs;

use App\Analytics\Support\AnalyticsSchema;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
class ProcessReplayChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $siteId,
        public string $sessionUuid,
        public ?string $visitorUuid,
        public array $events
    ) {}

    public function handle(): void
    {
        if (!AnalyticsSchema::hasTable('analytics_replay_recordings')) {
            return;
        }

        if (!AnalyticsSchema::hasColumn('analytics_replay_chunks', 'sequence')) {
            return;
        }

        $replayEvents = array_values(array_filter(
            $this->events,
            fn ($e) => str_starts_with((string) ($e['type'] ?? ''), 'replay_')
        ));

        if ($replayEvents === []) {
            return;
        }

        $recordingId = DB::table('analytics_replay_recordings')
            ->where('site_id', $this->siteId)
            ->where('session_uuid', $this->sessionUuid)
            ->value('id');

        if (!$recordingId) {
            $recordingId = DB::table('analytics_replay_recordings')->insertGetId([
                'site_id' => $this->siteId,
                'session_uuid' => $this->sessionUuid,
                'visitor_uuid' => $this->visitorUuid,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $payload = gzcompress(json_encode($replayEvents), 6);
        $seq = (int) DB::table('analytics_replay_chunks')->where('recording_id', $recordingId)->max('sequence') + 1;

        DB::table('analytics_replay_chunks')->insert([
            'recording_id' => $recordingId,
            'sequence' => $seq,
            'payload' => base64_encode($payload),
            'size_bytes' => strlen($payload),
            'created_at' => now(),
        ]);

        DB::table('analytics_replay_recordings')->where('id', $recordingId)->update([
            'event_count' => DB::raw('event_count + ' . count($replayEvents)),
            'size_bytes' => DB::raw('size_bytes + ' . strlen($payload)),
            'updated_at' => now(),
        ]);
    }
}
