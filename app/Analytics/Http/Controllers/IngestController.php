<?php

namespace App\Analytics\Http\Controllers;

use App\Analytics\Http\Requests\AnalyticsIngestRequest;
use App\Analytics\Services\AnalyticsIngestionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IngestController extends Controller
{
    public function __construct(private readonly AnalyticsIngestionService $ingestion) {}

    public function collect(AnalyticsIngestRequest $request): JsonResponse
    {
        if (!config('analytics.enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Analytics disabled'], 503);
        }

        $site = $request->attributes->get('analytics_site');
        $result = $this->ingestion->accept(
            $site,
            $request->validated(),
            $request->header('Origin')
        );

        $status = $result['status'] ?? 202;

        return response()->json([
            'success' => $result['accepted'] ?? false,
            'queued' => $result['queued'] ?? 0,
            'message' => $result['message'] ?? 'Accepted',
        ], $status);
    }
}
