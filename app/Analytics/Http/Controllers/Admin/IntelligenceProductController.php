<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsProductInsightsService;
use App\Analytics\Support\AnalyticsSchema;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class IntelligenceProductController extends AdminController
{
    public function __construct(
        private readonly AnalyticsProductInsightsService $products,
        private readonly EloquentAnalyticsSiteRepository $sites,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        try {
            $site = $this->resolveSite($request);
            [$from, $to] = $this->dateRange($request);

            return response()->json([
                'success' => true,
                'data' => $this->products->catalog($site->id, $from, $to, $request->input('search')),
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'product catalog');
        }
    }

    public function show(Request $request, int $productId): JsonResponse
    {
        try {
            $site = $this->resolveSite($request);
            [$from, $to] = $this->dateRange($request);

            return response()->json([
                'success' => true,
                'data' => $this->products->detail($site->id, $productId, $from, $to),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'product insights', $productId);
        }
    }

    /** @return array{0: string, 1: string} */
    private function dateRange(Request $request): array
    {
        $from = $request->input('from', now()->subDays(29)->toDateString());
        $to = $request->input('to', now()->toDateString());

        if (strtotime($from) === false || strtotime($to) === false) {
            abort(422, 'Invalid date range.');
        }

        if (strtotime($from) > strtotime($to)) {
            [$from, $to] = [$to, $from];
        }

        $maxDays = 366;
        if ((strtotime($to) - strtotime($from)) / 86400 > $maxDays) {
            $from = date('Y-m-d', strtotime($to . " -{$maxDays} days"));
        }

        return [$from, $to];
    }

    private function errorResponse(Throwable $e, string $context, ?int $productId = null): JsonResponse
    {
        Log::error("Intelligence {$context} failed", [
            'product_id' => $productId,
            'message' => $e->getMessage(),
            'exception' => $e::class,
        ]);

        if ($e instanceof QueryException && !AnalyticsSchema::isInstalled()) {
            return response()->json([
                'success' => false,
                'message' => 'Analytics tables are missing. Run: php artisan migrate',
            ], 503);
        }

        $message = config('app.debug')
            ? $e->getMessage()
            : 'Unable to load product analytics. Please try again.';

        return response()->json(['success' => false, 'message' => $message], 500);
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
