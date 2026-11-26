<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MarketingCampaignRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\MarketingCampaignResource;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignSpend;
use App\Services\CartRecoveryService;
use App\Services\MarketingCampaignService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingCampaignController extends AdminController implements HasMiddleware
{
    public function __construct(
        protected MarketingCampaignService $campaigns,
        protected CartRecoveryService $recovery,
    ) {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:campaigns'),
        ];
    }

    public function index(PaginateRequest $request)
    {
        try {
            return MarketingCampaignResource::collection($this->campaigns->list($request));
        } catch (\Throwable $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function store(MarketingCampaignRequest $request)
    {
        try {
            return new MarketingCampaignResource($this->campaigns->store($request->validated()));
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(MarketingCampaign $campaign)
    {
        try {
            return new MarketingCampaignResource($this->campaigns->show($campaign));
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(MarketingCampaignRequest $request, MarketingCampaign $campaign)
    {
        try {
            return new MarketingCampaignResource($this->campaigns->update($campaign, $request->validated()));
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(MarketingCampaign $campaign)
    {
        try {
            $this->campaigns->destroy($campaign);

            return response(['status' => true, 'message' => 'Deleted']);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function addSpend(Request $request, MarketingCampaign $campaign)
    {
        $data = $request->validate([
            'spend_date' => ['nullable', 'date'],
            'spend' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $spend = $this->campaigns->addSpend($campaign, $data);

            return response(['status' => true, 'data' => $spend]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteSpend(MarketingCampaignSpend $spend)
    {
        try {
            $this->campaigns->deleteSpend($spend);

            return response(['status' => true, 'message' => 'Spend deleted']);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function performance(Request $request)
    {
        try {
            return response([
                'status' => true,
                'data' => $this->campaigns->performance(
                    $request->input('from'),
                    $request->input('to')
                ),
            ]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function catalog(Request $request)
    {
        try {
            // Metadata only in admin UI — full items stream via /feeds/meta-catalog.csv
            return response(['status' => true, 'data' => $this->campaigns->catalogFeed(false)]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function audiences(Request $request)
    {
        try {
            $segment = $request->input('segment', 'all');

            return response([
                'status' => true,
                'data' => $this->campaigns->audienceExport($segment, false),
            ]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function exportAudienceCsv(Request $request): StreamedResponse
    {
        $segment = $request->input('segment', 'all');
        $csv = $this->campaigns->audienceCsv($segment);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'meta-audience-' . $segment . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function syncAudience(Request $request)
    {
        try {
            $result = $this->campaigns->syncAudienceToMeta($request->input('segment', 'all'));

            return response(['status' => (bool) ($result['synced'] ?? false), 'data' => $result]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function recoveryOverview(Request $request)
    {
        try {
            $growth = app(\App\Analytics\Enterprise\Services\EnterpriseGrowthService::class);
            $siteId = \App\Analytics\Models\AnalyticsSite::query()->where('is_active', true)->orderBy('id')->value('id');
            $from = $request->input('from', now()->subDays(6)->toDateString());
            $to = $request->input('to', now()->toDateString());

            $data = $siteId
                ? $growth->recoveryOverview((int) $siteId, $from, $to)
                : ['kpis' => [], 'queue' => [], 'campaigns' => [], 'recent_abandons' => []];

            return response(['status' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function recoveryEnqueue(Request $request)
    {
        try {
            $result = $this->recovery->enqueueFromAbandons(
                null,
                (int) $request->input('hours', 72)
            );

            return response(['status' => true, 'data' => $result]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function recoveryProcess()
    {
        try {
            return response(['status' => true, 'data' => $this->recovery->processDue()]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function attribution(Request $request)
    {
        try {
            $growth = app(\App\Analytics\Enterprise\Services\EnterpriseGrowthService::class);
            $siteId = \App\Analytics\Models\AnalyticsSite::query()->where('is_active', true)->orderBy('id')->value('id');
            $from = $request->input('from', now()->subDays(29)->toDateString());
            $to = $request->input('to', now()->toDateString());

            $data = $siteId
                ? $growth->attributionReport((int) $siteId, $from, $to, $request->input('model', 'last_click'))
                : ['channels' => [], 'totals' => []];

            return response(['status' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
