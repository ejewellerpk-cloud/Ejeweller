<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsDashboardService;
use App\Analytics\Services\AnalyticsRealtimeService;
use App\Analytics\Services\AnalyticsSettingsService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class IntelligenceDashboardController extends AdminController
{
    public function __construct(
        private readonly AnalyticsDashboardService $dashboard,
        private readonly EloquentAnalyticsSiteRepository $sites,
        private readonly AnalyticsSettingsService $settings,
    ) {}

    public function sites(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $list = $this->sites->listForUser($userId);

        if (empty($list)) {
            $this->settings->resolveOrCreateSite($userId);
            $list = $this->sites->listForUser($userId);
        }

        $today = Carbon::now(config('app.timezone', 'UTC'))->toDateString();
        $defaultFrom = Carbon::parse($today)->subDays(6)->toDateString();

        $siteIds = collect($list)->pluck('id')->filter()->all();
        $defaultSiteId = !empty($siteIds)
            ? (AnalyticsEvent::query()
                ->whereIn('site_id', $siteIds)
                ->where('occurred_at', '>=', Carbon::now()->subDays(7))
                ->selectRaw('site_id, COUNT(*) as total')
                ->groupBy('site_id')
                ->orderByDesc('total')
                ->value('site_id') ?? $list[0]->id)
            : null;

        return response()->json([
            'success' => true,
            'data' => collect($list)->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'domain' => $s->domain,
                'public_key' => $s->public_key,
                'is_active' => (bool) $s->is_active,
            ])->values(),
            'meta' => [
                'default_site_id' => $defaultSiteId,
                'server_today' => $today,
                'default_from' => $defaultFrom,
                'default_to' => $today,
            ],
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->overview($site->id, $from, $to),
        ]);
    }

    public function realtime(Request $request, AnalyticsRealtimeService $realtime): JsonResponse
    {
        $site = $this->resolveSite($request);

        return response()->json([
            'success' => true,
            'data' => $realtime->snapshot($site->id),
        ]);
    }

    public function funnel(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->funnel($site->id, $from, $to),
        ]);
    }

    public function sources(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->sources($site->id, $from, $to),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->topProducts($site->id, $from, $to),
        ]);
    }

    private function resolveSite(Request $request)
    {
        $userId = $request->user()->id;
        $siteId = (int) $request->input('site_id', $request->header('X-Analytics-Site-Id'));

        if ($siteId > 0) {
            $site = $this->sites->findForUser($siteId, $userId);
            if ($site) {
                return $site;
            }
        }

        $list = $this->sites->listForUser($userId);
        if (!empty($list)) {
            return $list[0];
        }

        abort(404, 'Analytics site not found');
    }
}
