<?php

namespace App\Analytics\Jobs;

use App\Analytics\Services\AnalyticsIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAnalyticsIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $siteId) {}

    public function handle(AnalyticsIngestionService $ingestion): void
    {
        $ingestion->drainSite($this->siteId);
    }
}
