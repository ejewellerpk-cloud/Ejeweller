<?php

namespace App\Analytics\Http\Controllers;

use App\Analytics\Http\Requests\AnalyticsIngestRequest;
use App\Analytics\Services\AnalyticsIngestionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class IngestController extends Controller
{
    public function __construct(private readonly AnalyticsIngestionService $ingestion) {}

    public function collect(AnalyticsIngestRequest $request): JsonResponse
    {
        // Soft-disable: 204 avoids noisy browser console 503s; storefront UX is unaffected.
        if (!config('analytics.enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Analytics disabled'], 204);
        }

        try {
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
        } catch (Throwable $e) {
            Log::error('Analytics collect endpoint error', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            // Still accept client-side silently; failure is logged server-side for ops.
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Analytics ingest failed',
            ], 204);
        }
    }
}
