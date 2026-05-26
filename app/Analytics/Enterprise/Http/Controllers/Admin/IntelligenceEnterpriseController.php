<?php

namespace App\Analytics\Enterprise\Http\Controllers\Admin;

use App\Analytics\Enterprise\Jobs\GenerateAiInsightsJob;
use App\Analytics\Enterprise\Services\HeatmapService;
use App\Analytics\Enterprise\Services\InsightsEngineService;
use App\Analytics\Enterprise\Services\JourneyService;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Support\AnalyticsSchema;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntelligenceEnterpriseController extends Controller
{
    public function __construct(
        private readonly HeatmapService $heatmaps,
        private readonly InsightsEngineService $insights,
        private readonly JourneyService $journey,
    ) {}

    public function features(): JsonResponse
    {
        return response()->json([
            'data' => config('analytics_enterprise.features', []),
            'enabled' => config('analytics_enterprise.enabled', true),
        ]);
    }

    public function heatmapPages(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->heatmaps->pages(
                $siteId,
                $request->input('from'),
                $request->input('to'),
                $request->input('device')
            ),
        ]);
    }

    public function heatmapSnapshot(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        $path = $request->input('page_path', '/');

        return response()->json([
            'data' => $this->heatmaps->snapshot(
                $siteId,
                $path,
                $request->input('from'),
                $request->input('to'),
                $request->input('type', 'click'),
                $request->input('device')
            ),
        ]);
    }

    public function replaySessions(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        if (!AnalyticsSchema::hasTable('analytics_replay_recordings')) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('analytics_replay_recordings')
            ->where('site_id', $siteId)
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function insights(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->insights->list($siteId, $request->input('status', 'active')),
        ]);
    }

    public function generateInsights(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        GenerateAiInsightsJob::dispatch($siteId)->onQueue(config('analytics_enterprise.behavior_ingest.queue', 'analytics'));

        return response()->json(['success' => true, 'message' => 'Insight generation queued']);
    }

    public function dismissInsight(Request $request, int $id): JsonResponse
    {
        $siteId = $this->siteId($request);
        $ok = $this->insights->dismiss($siteId, $id);

        return response()->json(['success' => $ok]);
    }

    public function journeyVisitor(Request $request, string $visitorUuid): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->journey->forVisitor($siteId, $visitorUuid),
        ]);
    }

    public function segments(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        if (!AnalyticsSchema::hasTable('analytics_customer_scores')) {
            return response()->json(['data' => $this->defaultSegments()]);
        }

        $counts = DB::table('analytics_customer_scores')
            ->where('site_id', $siteId)
            ->select('segment_slug', DB::raw('COUNT(*) as customers'))
            ->groupBy('segment_slug')
            ->get();

        return response()->json(['data' => $counts]);
    }

    public function experiments(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        if (!AnalyticsSchema::hasTable('analytics_experiments')) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('analytics_experiments')->where('site_id', $siteId)->orderByDesc('id')->get();

        return response()->json(['data' => $rows]);
    }

    public function attribution(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        if (!AnalyticsSchema::hasTable('analytics_attribution_touches')) {
            return response()->json(['data' => ['model' => $request->input('model', 'last_click'), 'channels' => []]]);
        }

        $rows = DB::table('analytics_attribution_touches')
            ->where('site_id', $siteId)
            ->whereBetween('touched_at', [$request->input('from'), $request->input('to')])
            ->select('source', DB::raw('SUM(revenue) as revenue'), DB::raw('COUNT(*) as touches'))
            ->groupBy('source')
            ->get();

        return response()->json(['data' => ['model' => $request->input('model', 'last_click'), 'channels' => $rows]]);
    }

    public function alertRules(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        if (!AnalyticsSchema::hasTable('analytics_alert_rules')) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => DB::table('analytics_alert_rules')->where('site_id', $siteId)->get(),
        ]);
    }

    public function billingPlans(): JsonResponse
    {
        if (!AnalyticsSchema::hasTable('analytics_saas_plans')) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => DB::table('analytics_saas_plans')->where('is_active', true)->get(),
        ]);
    }

    private function siteId(Request $request): int
    {
        $siteId = (int) $request->input('site_id', $request->header('X-Analytics-Site-Id'));
        abort_unless($siteId > 0, 422, 'site_id required');
        abort_unless(
            AnalyticsSite::query()->where('id', $siteId)->where('is_active', true)->exists(),
            404,
            'Analytics site not found'
        );

        return $siteId;
    }

    private function defaultSegments(): array
    {
        return [
            ['segment_slug' => 'vip', 'customers' => 0],
            ['segment_slug' => 'at_risk', 'customers' => 0],
            ['segment_slug' => 'new', 'customers' => 0],
            ['segment_slug' => 'repeat', 'customers' => 0],
        ];
    }
}
