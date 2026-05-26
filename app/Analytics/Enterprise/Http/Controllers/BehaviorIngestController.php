<?php

namespace App\Analytics\Enterprise\Http\Controllers;

use App\Analytics\Enterprise\Http\Requests\BehaviorIngestRequest;
use App\Analytics\Enterprise\Services\BehaviorIngestionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BehaviorIngestController extends Controller
{
    public function __construct(private readonly BehaviorIngestionService $ingestion) {}

    public function collect(BehaviorIngestRequest $request): JsonResponse
    {
        if (!config('analytics_enterprise.enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Enterprise analytics disabled'], 503);
        }

        $site = $request->attributes->get('analytics_site');
        $result = $this->ingestion->accept($site, $request->validated());

        return response()->json([
            'success' => $result['accepted'] ?? false,
            'queued' => $result['queued'] ?? 0,
            'message' => $result['message'] ?? 'Accepted',
        ], $result['status'] ?? 202);
    }
}
