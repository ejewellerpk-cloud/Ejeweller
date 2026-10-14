<?php

namespace App\Analytics\Enterprise\Http\Controllers\Admin;

use App\Analytics\Enterprise\Jobs\GenerateAiInsightsJob;
use App\Analytics\Enterprise\Services\EnterpriseGrowthService;
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
        private readonly EnterpriseGrowthService $growth,
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

    public function journeyVisitors(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->growth->recentJourneyVisitors($siteId, (int) $request->input('limit', 30)),
        ]);
    }

    public function cartRecovery(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        $from = $request->input('from', now()->subDays(6)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'data' => $this->growth->recoveryOverview($siteId, $from, $to),
        ]);
    }

    public function ensureRecoveryCampaign(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->growth->ensureDefaultCampaign($siteId),
            'success' => true,
        ]);
    }

    public function segments(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->growth->segmentsOverview($siteId),
        ]);
    }

    public function syncSegments(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => $this->growth->syncSegmentScores($siteId),
            'success' => true,
        ]);
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

    public function storeExperiment(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        if (!AnalyticsSchema::hasTable('analytics_experiments')) {
            return response()->json(['success' => false, 'message' => 'Experiments table missing'], 422);
        }

        $key = $request->input('experiment_key') ?: $request->input('key') ?: ('exp_' . substr(md5(uniqid('', true)), 0, 8));
        $id = DB::table('analytics_experiments')->insertGetId([
            'site_id' => $siteId,
            'experiment_key' => $key,
            'name' => $request->input('name', 'Untitled experiment'),
            'status' => $request->input('status', 'draft'),
            'variants' => json_encode($request->input('variants', [
                ['key' => 'control', 'weight' => 50],
                ['key' => 'variant_a', 'weight' => 50],
            ])),
            'traffic_percent' => (int) $request->input('traffic_percent', 100),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => DB::table('analytics_experiments')->where('id', $id)->first(),
        ]);
    }

    public function attribution(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);
        $from = $request->input('from', now()->subDays(6)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $model = $request->input('model', 'last_click');

        return response()->json([
            'data' => $this->growth->attributionReport($siteId, $from, $to, $model),
        ]);
    }

    public function storeAttributionCost(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        try {
            $row = $this->growth->saveCampaignCost($siteId, $request->all());

            return response()->json(['success' => true, 'data' => $row]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function alertRules(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'data' => [
                'rules' => $this->growth->alertRules($siteId),
                'incidents' => $this->growth->recentIncidents($siteId),
            ],
        ]);
    }

    public function storeAlertRule(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        try {
            $rule = $this->growth->storeAlertRule($siteId, $request->all());

            return response()->json(['success' => true, 'data' => $rule]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function toggleAlertRule(Request $request, int $id): JsonResponse
    {
        $siteId = $this->siteId($request);
        $active = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);
        $ok = $this->growth->toggleAlertRule($siteId, $id, $active);

        return response()->json(['success' => $ok]);
    }

    public function seedAlertRules(Request $request): JsonResponse
    {
        $siteId = $this->siteId($request);

        return response()->json([
            'success' => true,
            'data' => $this->growth->seedDefaultAlerts($siteId),
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

}
