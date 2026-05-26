<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsProductInsightsService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntelligenceProductController extends AdminController
{
    public function __construct(
        private readonly AnalyticsProductInsightsService $products,
        private readonly EloquentAnalyticsSiteRepository $sites,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(29)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->products->catalog($site->id, $from, $to, $request->input('search')),
        ]);
    }

    public function show(Request $request, int $productId): JsonResponse
    {
        $site = $this->resolveSite($request);
        $from = $request->input('from', now()->subDays(29)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->products->detail($site->id, $productId, $from, $to),
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
