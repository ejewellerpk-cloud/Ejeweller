<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Repositories\EloquentAnalyticsSiteRepository;
use App\Analytics\Services\AnalyticsAdvancedService;
use App\Analytics\Services\AnalyticsDashboardService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class IntelligenceAdvancedController extends AdminController
{
    public function __construct(
        private readonly AnalyticsAdvancedService $advanced,
        private readonly AnalyticsDashboardService $dashboard,
        private readonly EloquentAnalyticsSiteRepository $sites,
    ) {}

    public function cohortRetention(): JsonResponse
    {
        return $this->ok($this->advanced->cohortRetention());
    }

    public function rfm(): JsonResponse
    {
        return $this->ok($this->advanced->rfmSegments());
    }

    public function productAffinity(Request $request): JsonResponse
    {
        $limit = min(30, max(5, (int) $request->input('limit', 15)));

        return $this->ok($this->advanced->productAffinity($limit));
    }

    public function payments(Request $request): JsonResponse
    {
        [$from, $to] = $this->dates($request);

        return $this->ok($this->advanced->paymentSplit($from, $to));
    }

    public function returns(Request $request): JsonResponse
    {
        [$from, $to] = $this->dates($request);

        return $this->ok($this->advanced->returnAnalytics($from, $to));
    }

    public function geoDevice(Request $request): JsonResponse
    {
        $site = $this->resolveSite($request);
        [$from, $to] = $this->dates($request);

        return $this->ok($this->advanced->geoAndDevice($site->id, $from, $to));
    }

    public function hourlyHeatmap(Request $request): JsonResponse
    {
        [$from, $to] = $this->dates($request);

        return $this->ok($this->advanced->hourlyHeatmap($from, $to));
    }

    public function inventoryForecast(): JsonResponse
    {
        return $this->ok($this->advanced->inventoryForecast());
    }

    public function multiStoreCompare(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $list = $this->sites->listForUser($userId);
        $siteIds = collect($list)->pluck('id')->all();
        [$from, $to] = $this->dates($request);

        return $this->ok($this->advanced->multiStoreCompare($siteIds, $from, $to, $this->dashboard));
    }

    public function reportScheduleShow(Request $request): JsonResponse
    {
        $key = 'analytics_report_schedule:' . $request->user()->id;
        $data = Cache::get($key, [
            'enabled' => false,
            'frequency' => 'weekly',
            'email' => $request->user()->email,
            'day' => 'monday',
        ]);

        return $this->ok($data);
    }

    public function reportScheduleSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'frequency' => 'required|in:daily,weekly,monthly',
            'email' => 'required|email',
            'day' => 'nullable|string',
        ]);

        $key = 'analytics_report_schedule:' . $request->user()->id;
        Cache::forever($key, $validated);

        return $this->ok([
            'saved' => true,
            'schedule' => $validated,
            'note' => 'Add Laravel scheduler on server: php artisan schedule:run (cron) to enable automatic sends.',
        ]);
    }

    public function reportSendNow(Request $request, \App\Analytics\Http\Controllers\Admin\IntelligenceCommerceController $commerce): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        $request->merge([
            'from' => $request->input('from', now()->subDays(6)->toDateString()),
            'to' => $request->input('to', now()->toDateString()),
        ]);

        $email = $request->user()->email;
        try {
            if (config('mail.default') && config('mail.from.address')) {
                Mail::raw(
                    'Your Shopperzz analytics export is attached. Configure full CSV attachment in production mailer.',
                    fn ($m) => $m->to($email)->subject('Shopperzz Analytics Report')
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $commerce->exportReport($request);
    }

    private function dates(Request $request): array
    {
        $from = $request->input('from', now()->subDays(29)->toDateString());
        $to = $request->input('to', now()->toDateString());

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

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }
}
