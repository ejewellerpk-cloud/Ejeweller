<?php

namespace App\Analytics\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsRealtimeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $siteId,
        public array $snapshot,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('analytics.site.' . $this->siteId);
    }

    public function broadcastAs(): string
    {
        return 'AnalyticsRealtimeUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'site_id' => $this->siteId,
            'snapshot' => $this->snapshot,
        ];
    }
}
