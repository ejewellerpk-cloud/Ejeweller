<?php

namespace App\Analytics\Enterprise\Jobs;

use App\Analytics\Enterprise\Services\HeatmapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateHeatmapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $siteId) {}

    public function handle(HeatmapService $heatmaps): void
    {
        $heatmaps->aggregateSite($this->siteId);
    }
}
