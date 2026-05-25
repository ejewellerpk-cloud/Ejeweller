<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsDashboardService;
use App\Analytics\Services\AnalyticsRealtimeService;
use App\Analytics\Services\AnalyticsSettingsService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IntelligenceDashboardController extends AdminController
{
    public function __construct(
        private readonly AnalyticsDashboardService $dashboard,
        private readonly EloquentAnalyticsSiteRepository $sites,
        private readonly AnalyticsSettingsService $settings,
    ) {}

    public function sites(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $list = $this->sites->listForUser($userId);

            if (empty($list)) {
                $this->settings->resolveOrCreateSite($userId);
                $list = $this->sites->listForUser($userId);
            }

            $today = Carbon::now(config('app.timezone', 'UTC'))->toDateString();
            $defaultFrom = Carbon::parse($today, config('app.timezone', 'UTC'))->subDays(6)->toDateString();
            $defaultSiteId = !empty($list) ? $list[0]->id : null;

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
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Could not load analytics sites.',
            ], 500);
        }
    }

    public function overview(Request $request): JsonResponse
    {
        return $this->metricsResponse($request, function (int $siteId, string $from, string $to) {
            return $this->dashboard->overview($siteId, $from, $to);
        });
    }

    public function realtime(Request $request, AnalyticsRealtimeService $realtime): JsonResponse
    {
        return $this->metricsResponse($request, function (int $siteId) use ($realtime) {
            return $realtime->snapshot($siteId);
        }, dateRange: false);
    }

    public function funnel(Request $request): JsonResponse
    {
        return $this->metricsResponse($request, function (int $siteId, string $from, string $to) {
            return $this->dashboard->funnel($siteId, $from, $to);
        });
    }

    public function sources(Request $request): JsonResponse
    {
        return $this->metricsResponse($request, function (int $siteId, string $from, string $to) {
            return $this->dashboard->sources($siteId, $from, $to);
        });
    }

    public function products(Request $request): JsonResponse
    {
        return $this->metricsResponse($request, function (int $siteId, string $from, string $to) {
            return $this->dashboard->topProducts($siteId, $from, $to);
        });
    }

    /**
     * @param callable(int, string, string): mixed|callable(int): mixed $callback
     */
    private function metricsResponse(Request $request, callable $callback, bool $dateRange = true): JsonResponse
    {
        try {
            $site = $this->resolveSite($request);
            if ($dateRange) {
                [$from, $to] = $this->dateRange($request);
                $data = $callback($site->id, $from, $to);
            } else {
                $data = $callback($site->id);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Analytics tables missing or incomplete. Run: php artisan migrate',
            ], 500);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Could not load analytics metrics.',
            ], 500);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dateRange(Request $request): array
    {
        $tz = config('app.timezone', 'UTC');
        $today = Carbon::now($tz)->toDateString();
        $from = trim((string) $request->input('from', ''));
        $to = trim((string) $request->input('to', ''));

        if ($from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = Carbon::parse($today, $tz)->subDays(6)->toDateString();
        }
        if ($to === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $today;
        }

        return [$from, $to];
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
