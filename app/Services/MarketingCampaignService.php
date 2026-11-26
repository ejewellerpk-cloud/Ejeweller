<?php

namespace App\Services;

use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Support\AnalyticsSchema;
use App\Http\Requests\PaginateRequest;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignSpend;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Dipokhalder\Settings\Facades\Settings;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MarketingCampaignService
{
    protected array $campaignFilter = [
        'name', 'channel', 'status', 'objective', 'utm_campaign',
    ];

    public function list(PaginateRequest $request): LengthAwarePaginator
    {
        $method = $request->get('paginate', 0) > 0 ? 'paginate' : 'get';
        $requests = $request->all();
        $count = $request->get('paginate') ?: 20;
        $column = in_array($request->get('order_column'), $this->campaignFilter, true)
            ? $request->get('order_column')
            : 'id';
        $type = in_array(strtolower((string) $request->get('order_type')), ['asc', 'desc'], true)
            ? $request->get('order_type')
            : 'desc';

        return MarketingCampaign::query()
            ->withSum(['spends as total_spend'], 'spend')
            ->where(function ($query) use ($requests) {
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->campaignFilter, true) && $request !== null && $request !== '') {
                        $query->where($key, 'like', '%' . $request . '%');
                    }
                }
            })
            ->orderBy($column, $type)
            ->$method($count);
    }

    public function store(array $data): MarketingCampaign
    {
        if (empty($data['utm_campaign'])) {
            $data['utm_campaign'] = MarketingCampaign::slugifyCampaignName($data['name'] ?? 'campaign');
        }

        $data['utm_source'] = $data['utm_source'] ?? $this->defaultSourceForChannel($data['channel'] ?? 'meta');
        $data['utm_medium'] = $data['utm_medium'] ?? 'cpc';
        $data['landing_path'] = $data['landing_path'] ?? '/';
        $data['currency'] = $data['currency'] ?? $this->defaultCurrency();
        $data['status'] = $data['status'] ?? 'draft';

        return MarketingCampaign::query()->create($data);
    }

    public function show(MarketingCampaign $campaign): MarketingCampaign
    {
        return $campaign->load(['spends' => fn ($q) => $q->orderByDesc('spend_date')])
            ->loadSum(['spends as total_spend'], 'spend');
    }

    public function update(MarketingCampaign $campaign, array $data): MarketingCampaign
    {
        if (isset($data['utm_campaign']) && $data['utm_campaign'] !== $campaign->utm_campaign) {
            $exists = MarketingCampaign::query()
                ->where('utm_campaign', $data['utm_campaign'])
                ->where('id', '!=', $campaign->id)
                ->exists();
            if ($exists) {
                throw new Exception('UTM campaign slug already in use.');
            }
        }

        $campaign->update($data);

        return $this->show($campaign->fresh());
    }

    public function destroy(MarketingCampaign $campaign): void
    {
        $campaign->delete();
    }

    public function addSpend(MarketingCampaign $campaign, array $data): MarketingCampaignSpend
    {
        $spend = $campaign->spends()->create([
            'spend_date' => $data['spend_date'] ?? now()->toDateString(),
            'spend' => (float) ($data['spend'] ?? 0),
            'currency' => $data['currency'] ?? $campaign->currency ?? $this->defaultCurrency(),
            'impressions' => $data['impressions'] ?? null,
            'clicks' => $data['clicks'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->mirrorSpendToAnalytics($campaign, $spend);

        return $spend;
    }

    public function deleteSpend(MarketingCampaignSpend $spend): void
    {
        $spend->delete();
    }

    public function performance(?string $from = null, ?string $to = null): array
    {
        $from = $from ?: now()->subDays(29)->toDateString();
        $to = $to ?: now()->toDateString();
        $siteId = $this->defaultAnalyticsSiteId();

        $campaigns = MarketingCampaign::query()
            ->withSum(['spends as period_spend' => function ($q) use ($from, $to) {
                $q->whereBetween('spend_date', [$from, $to]);
            }], 'spend')
            ->orderByDesc('id')
            ->get();

        $attribution = $this->campaignAttribution($siteId, $from, $to);

        $rows = $campaigns->map(function (MarketingCampaign $c) use ($attribution) {
            $key = strtolower((string) $c->utm_campaign);
            $attr = $attribution[$key] ?? ['orders' => 0, 'revenue' => 0.0, 'sessions' => 0];
            $spend = (float) ($c->period_spend ?? 0);
            $revenue = (float) $attr['revenue'];
            $orders = (int) $attr['orders'];

            return [
                'id' => $c->id,
                'name' => $c->name,
                'channel' => $c->channel,
                'status' => $c->status,
                'utm_campaign' => $c->utm_campaign,
                'tracking_url' => $c->tracking_url,
                'spend' => round($spend, 2),
                'sessions' => (int) $attr['sessions'],
                'orders' => $orders,
                'revenue' => round($revenue, 2),
                'roas' => $spend > 0 ? round($revenue / $spend, 2) : null,
                'cpa' => $orders > 0 && $spend > 0 ? round($spend / $orders, 2) : null,
            ];
        })->values()->all();

        $totalSpend = array_sum(array_column($rows, 'spend'));
        $totalRevenue = array_sum(array_column($rows, 'revenue'));
        $totalOrders = array_sum(array_column($rows, 'orders'));

        return [
            'from' => $from,
            'to' => $to,
            'campaigns' => $rows,
            'totals' => [
                'spend' => round($totalSpend, 2),
                'revenue' => round($totalRevenue, 2),
                'orders' => $totalOrders,
                'roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : null,
            ],
        ];
    }

    public function catalogFeed(): array
    {
        $currency = $this->defaultCurrency();
        $base = rtrim(config('app.url'), '/');

        $products = Product::query()
            ->with(['brand', 'category'])
            ->where('status', \App\Enums\Status::ACTIVE)
            ->orderBy('id')
            ->get();

        $items = [];
        foreach ($products as $product) {
            $price = (float) ($product->selling_price ?? 0);
            $inStock = (int) ($product->stock ?? 0) > 0;
            $items[] = [
                'id' => (string) $product->id,
                'title' => $product->name,
                'description' => Str::limit(strip_tags((string) ($product->description ?? $product->name)), 5000),
                'availability' => $inStock ? 'in stock' : 'out of stock',
                'condition' => 'new',
                'price' => number_format($price, 2, '.', '') . ' ' . $currency,
                'link' => $base . '/product/' . $product->slug,
                'image_link' => $product->cover ?: $product->image,
                'brand' => $product->brand?->name ?: Settings::group('company')->get('company_name') ?: config('app.name'),
                'google_product_category' => $product->category?->name,
            ];
        }

        return [
            'count' => count($items),
            'currency' => $currency,
            'items' => $items,
            'csv_url' => url('/feeds/meta-catalog.csv'),
            'instructions' => 'Use this CSV URL in Meta Commerce Manager → Catalog → Data Sources.',
        ];
    }

    public function catalogCsv(): string
    {
        $feed = $this->catalogFeed();
        $headers = ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'google_product_category'];
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);
        foreach ($feed['items'] as $item) {
            fputcsv($fh, array_map(fn ($h) => $item[$h] ?? '', $headers));
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $csv;
    }

    public function audienceExport(string $segment = 'all'): array
    {
        $query = User::query()->whereNotNull('email')->where('email', '!=', '');

        if ($segment !== 'all' && AnalyticsSchema::hasTable('analytics_customer_scores')) {
            $siteId = $this->defaultAnalyticsSiteId();
            $userIds = DB::table('analytics_customer_scores')
                ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
                ->where('segment_slug', $segment)
                ->whereNotNull('user_id')
                ->pluck('user_id');
            $query->whereIn('id', $userIds);
        }

        $users = $query->orderBy('id')->limit(50000)->get(['id', 'name', 'email', 'phone']);

        $rows = $users->map(function (User $user) {
            $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [''];

            return [
                'email' => strtolower(trim((string) $user->email)),
                'email_hash' => hash('sha256', strtolower(trim((string) $user->email))),
                'phone' => preg_replace('/\D+/', '', (string) ($user->phone ?? '')),
                'fn' => strtolower(trim($parts[0] ?? '')),
                'ln' => strtolower(trim($parts[1] ?? '')),
            ];
        })->values()->all();

        return [
            'segment' => $segment,
            'count' => count($rows),
            'rows' => $rows,
            'meta_ready' => true,
            'note' => 'Upload email hashes in Meta Ads Manager → Audiences → Custom Audience → Customer list.',
        ];
    }

    public function audienceCsv(string $segment = 'all'): string
    {
        $export = $this->audienceExport($segment);
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['email', 'phone', 'fn', 'ln']);
        foreach ($export['rows'] as $row) {
            fputcsv($fh, [$row['email'], $row['phone'], $row['fn'], $row['ln']]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $csv;
    }

    /**
     * Optional Graph API custom audience sync when Marketing API token + ad account are set.
     */
    public function syncAudienceToMeta(string $segment = 'all'): array
    {
        $token = Settings::group('site')->get('site_meta_marketing_token')
            ?: Settings::group('site')->get('site_facebook_capi_token');
        $adAccount = Settings::group('site')->get('site_meta_ad_account_id');

        if (!$token || !$adAccount) {
            return [
                'synced' => false,
                'message' => 'Set Meta Marketing token and Ad Account ID in Facebook CAPI settings, or download CSV for manual upload.',
            ];
        }

        $export = $this->audienceExport($segment);
        if ($export['count'] === 0) {
            return ['synced' => false, 'message' => 'No customers in this segment.'];
        }

        $accountId = str_starts_with((string) $adAccount, 'act_') ? $adAccount : ('act_' . $adAccount);

        $create = Http::asForm()->post("https://graph.facebook.com/v19.0/{$accountId}/customaudiences", [
            'access_token' => $token,
            'name' => 'Shopperzz ' . $segment . ' ' . now()->format('Y-m-d'),
            'subtype' => 'CUSTOM',
            'description' => 'Synced from Shopperzz segment: ' . $segment,
            'customer_file_source' => 'USER_PROVIDED_ONLY',
        ]);

        if (!$create->successful()) {
            return [
                'synced' => false,
                'message' => 'Meta audience create failed: ' . ($create->json('error.message') ?? $create->body()),
            ];
        }

        $audienceId = $create->json('id');
        $schema = ['EMAIL', 'PHONE', 'FN', 'LN'];
        $payload = [];
        foreach (array_slice($export['rows'], 0, 10000) as $row) {
            $payload[] = [
                $row['email_hash'],
                $row['phone'] ? hash('sha256', $row['phone']) : '',
                $row['fn'] ? hash('sha256', $row['fn']) : '',
                $row['ln'] ? hash('sha256', $row['ln']) : '',
            ];
        }

        $users = Http::asForm()->post("https://graph.facebook.com/v19.0/{$audienceId}/users", [
            'access_token' => $token,
            'payload' => json_encode([
                'schema' => $schema,
                'data' => $payload,
            ]),
        ]);

        if (!$users->successful()) {
            return [
                'synced' => false,
                'audience_id' => $audienceId,
                'message' => 'Audience created but user upload failed: ' . ($users->json('error.message') ?? $users->body()),
            ];
        }

        return [
            'synced' => true,
            'audience_id' => $audienceId,
            'uploaded' => count($payload),
            'message' => 'Custom audience synced to Meta.',
        ];
    }

    private function campaignAttribution(?int $siteId, string $from, string $to): array
    {
        if (!$siteId || !AnalyticsSchema::hasTable('analytics_sessions') || !AnalyticsSchema::hasTable('analytics_events')) {
            return [];
        }

        $tz = config('app.timezone', 'UTC');
        $fromAt = Carbon::parse($from, $tz)->startOfDay();
        $toAt = Carbon::parse($to, $tz)->endOfDay();

        $sessions = DB::table('analytics_sessions')
            ->where('site_id', $siteId)
            ->whereNotNull('campaign')
            ->where('campaign', '!=', '')
            ->whereBetween('started_at', [$fromAt, $toAt])
            ->select('campaign', DB::raw('COUNT(*) as sessions'))
            ->groupBy('campaign')
            ->pluck('sessions', 'campaign')
            ->all();

        $orders = DB::table('analytics_events as e')
            ->join('analytics_sessions as s', 's.id', '=', 'e.session_id')
            ->where('e.site_id', $siteId)
            ->whereIn('e.event_name', ['order_placed', 'order_confirmed', 'Purchase'])
            ->whereNotNull('s.campaign')
            ->where('s.campaign', '!=', '')
            ->whereBetween('e.occurred_at', [$fromAt, $toAt])
            ->select(
                's.campaign',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(COALESCE(e.revenue, 0)) as revenue')
            )
            ->groupBy('s.campaign')
            ->get();

        $out = [];
        foreach ($sessions as $campaign => $count) {
            $key = strtolower((string) $campaign);
            $out[$key] = [
                'sessions' => (int) $count,
                'orders' => 0,
                'revenue' => 0.0,
            ];
        }
        foreach ($orders as $row) {
            $key = strtolower((string) $row->campaign);
            $out[$key] = [
                'sessions' => (int) ($out[$key]['sessions'] ?? 0),
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
            ];
        }

        return $out;
    }

    private function mirrorSpendToAnalytics(MarketingCampaign $campaign, MarketingCampaignSpend $spend): void
    {
        if (!AnalyticsSchema::hasTable('analytics_campaign_costs')) {
            return;
        }

        $siteId = $this->defaultAnalyticsSiteId();
        if (!$siteId) {
            return;
        }

        $source = match ($campaign->channel) {
            'google' => 'google_ads',
            'tiktok' => 'tiktok',
            'meta', 'facebook', 'instagram' => 'facebook',
            default => $campaign->channel ?: 'ads',
        };

        DB::table('analytics_campaign_costs')->insert([
            'site_id' => $siteId,
            'cost_date' => $spend->spend_date->toDateString(),
            'source' => $source,
            'campaign' => $campaign->utm_campaign,
            'spend' => $spend->spend,
            'currency' => $spend->currency,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function defaultAnalyticsSiteId(): ?int
    {
        if (!class_exists(AnalyticsSite::class)) {
            return null;
        }

        return AnalyticsSite::query()->where('is_active', true)->orderBy('id')->value('id');
    }

    private function defaultSourceForChannel(string $channel): string
    {
        return match ($channel) {
            'google' => 'google',
            'tiktok' => 'tiktok',
            'meta', 'facebook', 'instagram' => 'facebook',
            default => $channel,
        };
    }

    private function defaultCurrency(): string
    {
        $currencyId = Settings::group('site')->get('site_default_currency');
        $code = \App\Models\Currency::find($currencyId)?->code;

        return $code ?: 'PKR';
    }
}
