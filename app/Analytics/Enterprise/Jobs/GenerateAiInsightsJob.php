<?php

namespace App\Analytics\Enterprise\Jobs;

use App\Analytics\Enterprise\Services\InsightsEngineService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $siteId) {}

    public function handle(InsightsEngineService $engine): void
    {
        $to = Carbon::today()->endOfDay()->toDateTimeString();
        $from = Carbon::today()->subDays(7)->startOfDay()->toDateTimeString();
        $engine->generateForSite($this->siteId, $from, $to);
    }
}
