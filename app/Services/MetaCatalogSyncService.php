<?php

namespace App\Services;

use App\Enums\Status;
use App\Libraries\AppLibrary;
use App\Models\MetaCatalogProduct;
use App\Models\MetaCatalogSyncLog;
use App\Models\Product;
use Dipokhalder\Settings\Facades\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MetaCatalogSyncService
{
    private const GRAPH_VERSION = 'v21.0';
    private const MIN_IMAGE_PX = 500;

    public function dashboard(): array
    {
        if (!$this->tablesReady()) {
            return $this->emptyDashboard('Run php artisan migrate --force to enable Meta catalog sync.');
        }

        $base = MetaCatalogProduct::query();
        $missingImages = Product::query()
            ->where('status', Status::ACTIVE)
            ->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'product'))
            ->count();

        return [
            'catalog_id' => $this->catalogId(),
            'configured' => (bool) $this->catalogId() && (bool) $this->token(),
            'totals' => [
                'tracked' => (clone $base)->count(),
                'synced' => (clone $base)->where('sync_status', MetaCatalogProduct::STATUS_SYNCED)->count(),
                'pending' => (clone $base)->whereIn('sync_status', [
                    MetaCatalogProduct::STATUS_PENDING,
                    MetaCatalogProduct::STATUS_STALE,
                ])->count(),
                'failed' => (clone $base)->where('sync_status', MetaCatalogProduct::STATUS_FAILED)->count(),
                'needs_attention' => (clone $base)->where('sync_status', MetaCatalogProduct::STATUS_NEEDS_ATTENTION)->count(),
                'missing_images' => $missingImages,
                'invalid_landing' => (clone $base)->whereJsonContains('validation_errors', [['code' => 'landing_page']])->count(),
            ],
            'last_synced_at' => optional((clone $base)->whereNotNull('last_synced_at')->max('last_synced_at')),
            'csv_url' => url('/feeds/meta-catalog.csv'),
            'recent' => MetaCatalogProduct::query()
                ->with(['product:id,name,sku,slug,selling_price,status'])
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get()
                ->map(fn (MetaCatalogProduct $row) => $this->statusRow($row))
                ->values()
                ->all(),
        ];
    }

    public function prepare(Product $product, bool $validateRemote = false): array
    {
        $product->loadMissing(['brand', 'category', 'videos', 'media', 'variations.productAttribute', 'variations.productAttributeOption']);

        $payload = $this->buildCatalogPayload($product);
        $hash = $this->contentHash($payload);
        $local = $this->validateLocal($product, $payload);

        $landing = null;
        if ($validateRemote || !empty($local['errors'])) {
            $landing = $this->validateLandingPage($payload['url'] ?? '');
            if (!($landing['ok'] ?? false)) {
                $local['errors'][] = [
                    'code' => 'landing_page',
                    'message' => $landing['message'] ?? 'Landing page is not publicly accessible.',
                ];
            } elseif (!empty($landing['warnings'])) {
                foreach ($landing['warnings'] as $w) {
                    $local['warnings'][] = ['code' => 'landing_page', 'message' => $w];
                }
            }
        }

        $images = $validateRemote
            ? $this->validateImages($payload)
            : ['ok' => true, 'checked' => false, 'errors' => [], 'warnings' => []];

        foreach ($images['errors'] ?? [] as $err) {
            $local['errors'][] = ['code' => 'image', 'message' => $err];
        }
        foreach ($images['warnings'] ?? [] as $warn) {
            $local['warnings'][] = ['code' => 'image', 'message' => $warn];
        }

        $record = $this->upsertRecord($product, $payload, $hash, $local);

        $this->log($product->id, 'prepare', empty($local['errors']) ? 'ok' : 'fail', empty($local['errors'])
            ? 'Product prepared for Meta catalog.'
            : 'Prepared with validation issues.', [
            'hash' => $hash,
            'errors' => $local['errors'],
            'warnings' => $local['warnings'],
        ]);

        return [
            'ok' => empty($local['errors']),
            'product_id' => $product->id,
            'retailer_id' => $payload['id'],
            'sync_status' => $record->sync_status,
            'content_hash' => $hash,
            'changed' => $record->wasRecentlyCreated || $record->content_hash !== $hash
                || $record->sync_status !== MetaCatalogProduct::STATUS_SYNCED,
            'already_in_catalog' => in_array($record->sync_status, [
                MetaCatalogProduct::STATUS_SYNCED,
                MetaCatalogProduct::STATUS_STALE,
            ], true),
            'preview' => $this->previewFromPayload($product, $payload, $local, $record),
            'payload' => $payload,
            'errors' => $local['errors'],
            'warnings' => $local['warnings'],
            'landing' => $landing,
            'images' => $images,
            'meta' => $this->statusRow($record->fresh('product')),
        ];
    }

    public function validate(Product $product): array
    {
        $prepared = $this->prepare($product, true);
        $record = MetaCatalogProduct::query()->where('product_id', $product->id)->first();

        if ($record) {
            $record->update([
                'last_validated_at' => now(),
                'validation_errors' => $prepared['errors'] ?? [],
                'validation_warnings' => $prepared['warnings'] ?? [],
                'sync_status' => empty($prepared['errors'])
                    ? ($record->sync_status === MetaCatalogProduct::STATUS_SYNCED
                        ? MetaCatalogProduct::STATUS_SYNCED
                        : ($record->sync_status === MetaCatalogProduct::STATUS_STALE
                            ? MetaCatalogProduct::STATUS_STALE
                            : MetaCatalogProduct::STATUS_PENDING))
                    : MetaCatalogProduct::STATUS_NEEDS_ATTENTION,
            ]);
        }

        $this->log($product->id, 'validate', empty($prepared['errors']) ? 'ok' : 'fail', empty($prepared['errors'])
            ? 'Product passed Meta validation checks.'
            : 'Product failed Meta validation checks.', [
            'errors' => $prepared['errors'] ?? [],
            'warnings' => $prepared['warnings'] ?? [],
        ]);

        return $prepared;
    }

    public function syncProduct(Product $product, bool $force = false): array
    {
        $prepared = $this->prepare($product, true);
        if (!empty($prepared['errors'])) {
            return [
                'ok' => false,
                'message' => 'Fix validation errors before syncing.',
                'data' => $prepared,
            ];
        }

        [$token, $catalogId] = $this->credentials();
        if (!$token || !$catalogId) {
            return [
                'ok' => false,
                'message' => 'Set Meta Catalog ID and Marketing API Token in Facebook CAPI settings.',
                'data' => $prepared,
            ];
        }

        $record = MetaCatalogProduct::query()->where('product_id', $product->id)->firstOrFail();
        if (!$force
            && $record->sync_status === MetaCatalogProduct::STATUS_SYNCED
            && $record->content_hash === $prepared['content_hash']
        ) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'Already synced — no content changes detected.',
                'data' => $prepared,
            ];
        }

        $method = $record->sync_status === MetaCatalogProduct::STATUS_SYNCED
            || $record->sync_status === MetaCatalogProduct::STATUS_STALE
            ? 'UPDATE'
            : 'CREATE';

        $result = $this->itemsBatch($token, $catalogId, [[
            'method' => $method,
            'data' => $prepared['payload'],
        ]], 3);

        if (!($result['ok'] ?? false)) {
            $record->update([
                'sync_status' => MetaCatalogProduct::STATUS_FAILED,
                'last_error' => $result['message'] ?? 'Sync failed',
                'meta_catalog_id' => $catalogId,
            ]);
            $this->log($product->id, 'sync', 'fail', $result['message'] ?? 'Sync failed', $result);

            return [
                'ok' => false,
                'message' => $result['message'] ?? 'Meta catalog sync failed.',
                'data' => array_merge($prepared, ['api' => $result]),
            ];
        }

        $itemErrors = $this->extractItemErrors($result['body'] ?? [], $prepared['payload']['id']);
        if ($itemErrors) {
            $record->update([
                'sync_status' => MetaCatalogProduct::STATUS_FAILED,
                'last_error' => implode('; ', $itemErrors),
                'validation_errors' => array_map(fn ($m) => ['code' => 'meta_api', 'message' => $m], $itemErrors),
                'batch_handle' => $result['handle'] ?? null,
                'meta_catalog_id' => $catalogId,
            ]);
            $this->log($product->id, 'sync', 'fail', implode('; ', $itemErrors), $result['body'] ?? []);

            return [
                'ok' => false,
                'message' => implode('; ', $itemErrors),
                'data' => array_merge($prepared, ['api' => $result]),
            ];
        }

        $record->update([
            'sync_status' => MetaCatalogProduct::STATUS_SYNCED,
            'content_hash' => $prepared['content_hash'],
            'last_payload' => $prepared['payload'],
            'last_error' => null,
            'validation_errors' => [],
            'validation_warnings' => $prepared['warnings'] ?? [],
            'batch_handle' => $result['handle'] ?? null,
            'meta_catalog_id' => $catalogId,
            'last_synced_at' => now(),
            'last_validated_at' => now(),
        ]);

        $this->log($product->id, 'sync', 'ok', "Product {$method} submitted to Meta catalog.", [
            'method' => $method,
            'handle' => $result['handle'] ?? null,
            'body' => $result['body'] ?? null,
        ]);

        return [
            'ok' => true,
            'message' => $method === 'CREATE'
                ? 'Product created in Meta Catalog (batch accepted).'
                : 'Product updated in Meta Catalog (batch accepted).',
            'method' => $method,
            'handle' => $result['handle'] ?? null,
            'data' => array_merge($prepared, [
                'meta' => $this->statusRow($record->fresh('product')),
                'api' => $result,
            ]),
        ];
    }

    public function syncMany(array $productIds, bool $force = false): array
    {
        $ids = collect($productIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $results = ['ok' => 0, 'failed' => 0, 'skipped' => 0, 'items' => []];

        foreach ($ids as $id) {
            $product = Product::query()->with(['brand', 'category', 'videos', 'media', 'variations.productAttribute', 'variations.productAttributeOption'])->find($id);
            if (!$product) {
                $results['failed']++;
                $results['items'][] = ['product_id' => $id, 'ok' => false, 'message' => 'Product not found'];
                continue;
            }

            $res = $this->syncProduct($product, $force);
            if (!empty($res['skipped'])) {
                $results['skipped']++;
            } elseif ($res['ok'] ?? false) {
                $results['ok']++;
            } else {
                $results['failed']++;
            }
            $results['items'][] = [
                'product_id' => $id,
                'name' => $product->name,
                'ok' => (bool) ($res['ok'] ?? false),
                'skipped' => (bool) ($res['skipped'] ?? false),
                'message' => $res['message'] ?? null,
            ];
        }

        return $results;
    }

    public function retryFailed(?array $productIds = null): array
    {
        $query = MetaCatalogProduct::query()->where('sync_status', MetaCatalogProduct::STATUS_FAILED);
        if ($productIds !== null) {
            $query->whereIn('product_id', $productIds);
        }

        return $this->syncMany($query->pluck('product_id')->all(), true);
    }

    public function syncStaleOrChanged(int $limit = 50): array
    {
        $ids = MetaCatalogProduct::query()
            ->whereIn('sync_status', [
                MetaCatalogProduct::STATUS_STALE,
                MetaCatalogProduct::STATUS_PENDING,
            ])
            ->orderBy('updated_at')
            ->limit($limit)
            ->pluck('product_id')
            ->all();

        return $this->syncMany($ids, true);
    }

    /**
     * Mark synced products stale when content hash no longer matches.
     */
    public function detectChange(Product $product): ?MetaCatalogProduct
    {
        if (!$this->tablesReady()) {
            return null;
        }

        $record = MetaCatalogProduct::query()->where('product_id', $product->id)->first();
        if (!$record) {
            return null;
        }

        $product->loadMissing(['brand', 'category', 'videos', 'media', 'variations.productAttribute', 'variations.productAttributeOption']);
        $payload = $this->buildCatalogPayload($product);
        $hash = $this->contentHash($payload);

        if ($record->content_hash && hash_equals((string) $record->content_hash, $hash)) {
            return $record;
        }

        $record->update([
            'content_hash' => $hash,
            'last_payload' => $payload,
            'sync_status' => $record->sync_status === MetaCatalogProduct::STATUS_SYNCED
                ? MetaCatalogProduct::STATUS_STALE
                : $record->sync_status,
        ]);

        $this->log($product->id, 'change_detect', 'ok', 'Product content changed — marked for Meta re-sync.', [
            'hash' => $hash,
        ]);

        return $record->fresh();
    }

    public function statusesForProductIds(array $ids): array
    {
        if (!$this->tablesReady() || empty($ids)) {
            return [];
        }

        return MetaCatalogProduct::query()
            ->whereIn('product_id', $ids)
            ->get()
            ->mapWithKeys(fn (MetaCatalogProduct $row) => [$row->product_id => $this->statusRow($row)])
            ->all();
    }

    public function buildCatalogPayload(Product $product): array
    {
        $currency = $this->currency();
        $base = rtrim((string) config('app.url'), '/');
        if (!Str::startsWith($base, 'https://')) {
            $base = 'https://' . ltrim(preg_replace('#^https?://#', '', $base), '/');
        }

        $slug = trim((string) $product->slug);
        $url = $base . '/product/' . $slug;

        $price = (float) ($product->selling_price ?? 0);
        $offerActive = AppLibrary::isProductOfferActive(
            $product->discount,
            $product->offer_start_date,
            $product->offer_end_date
        );
        $salePrice = $offerActive
            ? AppLibrary::productOfferPrice($price, $product->discount, $product->offer_start_date, $product->offer_end_date)
            : null;

        $images = $this->collectImageUrls($product);
        $primary = $images[0] ?? null;
        $additional = array_slice($images, 1, 20);

        $attrs = $this->extractAttributes($product);

        if ((int) $product->status !== Status::ACTIVE) {
            $availability = 'out of stock';
        } elseif ((int) $product->stock <= 0) {
            $availability = 'out of stock';
        } else {
            $availability = 'in stock';
        }

        $description = $this->cleanDescription((string) ($product->description ?: $product->name));
        $title = Str::limit(trim((string) $product->name), 150, '');

        $payload = [
            'id' => (string) $product->id,
            'title' => $title,
            'description' => Str::limit($description, 5000, ''),
            'availability' => $availability,
            'condition' => 'new',
            'price' => number_format($price, 2, '.', '') . ' ' . $currency,
            'link' => $url,
            'url' => $url,
            'image_link' => $primary,
            'image' => $primary ? [['url' => $primary]] : [],
            'brand' => $product->brand?->name ?: (Settings::group('company')->get('company_name') ?: 'Ejeweller'),
            'google_product_category' => $product->category?->name,
            'retailer_product_group_id' => (string) $product->id,
            'item_group_id' => (string) $product->id,
        ];

        if ($product->sku) {
            $payload['gtin'] = null;
            $payload['mpn'] = (string) $product->sku;
        }

        if ($salePrice !== null && $salePrice < $price) {
            $payload['sale_price'] = number_format($salePrice, 2, '.', '') . ' ' . $currency;
            if ($product->offer_start_date && $product->offer_end_date) {
                $start = date('c', strtotime((string) $product->offer_start_date));
                $end = date('c', strtotime((string) $product->offer_end_date));
                $payload['sale_price_effective_date'] = $start . '/' . $end;
            }
        }

        if ($additional) {
            $payload['additional_image_link'] = array_values($additional);
            foreach ($additional as $extra) {
                $payload['image'][] = ['url' => $extra];
            }
        }

        if (!empty($attrs['color'])) {
            $payload['color'] = $attrs['color'];
        }
        if (!empty($attrs['material'])) {
            $payload['material'] = $attrs['material'];
        }
        if (!empty($attrs['size'])) {
            $payload['size'] = $attrs['size'];
        }
        if (!empty($attrs['extra'])) {
            $payload['additional_variant_attribute'] = $attrs['extra'];
        }

        // Strip nulls
        return array_filter($payload, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    private function collectImageUrls(Product $product): array
    {
        $urls = [];
        foreach ($product->getMedia('product') as $media) {
            $url = $media->getUrl('preview') ?: $media->getUrl();
            if (!$url) {
                continue;
            }
            $absolute = $this->absolutizeUrl($url);
            if ($absolute && !in_array($absolute, $urls, true)) {
                $urls[] = $absolute;
            }
        }

        if (!$urls) {
            foreach ([$product->cover, $product->image] as $fallback) {
                $absolute = $this->absolutizeUrl((string) $fallback);
                if ($absolute && !str_contains($absolute, '/images/default/product/') && !in_array($absolute, $urls, true)) {
                    $urls[] = $absolute;
                }
            }
        }

        return $urls;
    }

    private function extractAttributes(Product $product): array
    {
        $color = null;
        $material = null;
        $size = null;
        $extra = [];

        foreach ($product->variations as $variation) {
            $attrName = strtolower((string) ($variation->productAttribute?->name ?? ''));
            $value = trim((string) ($variation->productAttributeOption?->name ?? ''));
            if ($value === '' || $attrName === '') {
                continue;
            }
            if (str_contains($attrName, 'color') || str_contains($attrName, 'colour')) {
                $color = $color ?: $value;
            } elseif (str_contains($attrName, 'material') || str_contains($attrName, 'metal')) {
                $material = $material ?: $value;
            } elseif (str_contains($attrName, 'size')) {
                $size = $size ?: $value;
            } else {
                $extra[] = $variation->productAttribute->name . ':' . $value;
            }
        }

        return [
            'color' => $color,
            'material' => $material,
            'size' => $size,
            'extra' => $extra ? implode(',', array_unique($extra)) : null,
        ];
    }

    private function validateLocal(Product $product, array $payload): array
    {
        $errors = [];
        $warnings = [];

        if (empty($payload['title'])) {
            $errors[] = ['code' => 'title', 'message' => 'Product name is required.'];
        }
        if (empty($payload['description'])) {
            $errors[] = ['code' => 'description', 'message' => 'Product description is required.'];
        }
        if (empty($payload['image_link'])) {
            $errors[] = ['code' => 'image', 'message' => 'Primary product image is missing.'];
        }
        if (empty($payload['price']) || preg_match('/^0+(\.0+)?\s/', (string) $payload['price'])) {
            $errors[] = ['code' => 'price', 'message' => 'Selling price must be greater than zero.'];
        }
        if (empty($product->slug)) {
            $errors[] = ['code' => 'slug', 'message' => 'Product slug is missing — cannot build landing URL.'];
        }
        if (empty($payload['brand'])) {
            $warnings[] = ['code' => 'brand', 'message' => 'Brand is empty — company name used as fallback.'];
        }
        if ((int) $product->status !== Status::ACTIVE) {
            $warnings[] = ['code' => 'status', 'message' => 'Product is inactive — availability will be out of stock.'];
        }
        if (empty($payload['google_product_category'])) {
            $warnings[] = ['code' => 'category', 'message' => 'Category missing — Meta category mapping skipped.'];
        }

        return compact('errors', 'warnings');
    }

    public function validateLandingPage(string $url): array
    {
        if ($url === '' || !Str::startsWith($url, 'https://')) {
            return ['ok' => false, 'message' => 'Landing URL must be absolute HTTPS.', 'status' => null];
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'EjewellerMetaCatalogBot/1.0'])
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            $status = $response->status();
            if ($status >= 200 && $status < 400) {
                $warnings = [];
                if ($status >= 300) {
                    $warnings[] = "Landing page returned HTTP {$status} (redirect).";
                }

                return ['ok' => true, 'status' => $status, 'final_url' => (string) $response->effectiveUri(), 'warnings' => $warnings];
            }

            return ['ok' => false, 'status' => $status, 'message' => "Landing page returned HTTP {$status}."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => null, 'message' => 'Landing page check failed: ' . $e->getMessage()];
        }
    }

    private function validateImages(array $payload): array
    {
        $errors = [];
        $warnings = [];
        $urls = [];
        if (!empty($payload['image_link'])) {
            $urls[] = $payload['image_link'];
        }
        foreach ($payload['additional_image_link'] ?? [] as $u) {
            $urls[] = $u;
        }
        $urls = array_values(array_unique($urls));

        if (!$urls) {
            return ['ok' => false, 'checked' => true, 'errors' => ['No product images found.'], 'warnings' => []];
        }

        foreach (array_slice($urls, 0, 5) as $url) {
            if (!Str::startsWith($url, 'https://')) {
                $errors[] = "Image must be HTTPS: {$url}";
                continue;
            }
            try {
                $head = Http::timeout(10)->withOptions(['allow_redirects' => true])->head($url);
                if (!$head->successful()) {
                    $get = Http::timeout(10)->withOptions(['allow_redirects' => true])->get($url);
                    if (!$get->successful()) {
                        $errors[] = "Broken image URL (HTTP {$get->status()}): {$url}";
                        continue;
                    }
                    $mime = $get->header('Content-Type') ?: '';
                } else {
                    $mime = $head->header('Content-Type') ?: '';
                }

                if ($mime && !Str::startsWith($mime, 'image/')) {
                    $warnings[] = "Unexpected MIME for image ({$mime}): {$url}";
                }

                // Dimension check when getimagesize is available over URL.
                if (function_exists('getimagesize')) {
                    $info = @getimagesize($url);
                    if (is_array($info)) {
                        [$w, $h] = $info;
                        if ($w < self::MIN_IMAGE_PX || $h < self::MIN_IMAGE_PX) {
                            $warnings[] = "Image {$w}x{$h} is below Meta recommended " . self::MIN_IMAGE_PX . "px: {$url}";
                        }
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = 'Image check failed: ' . $e->getMessage();
            }
        }

        return [
            'ok' => empty($errors),
            'checked' => true,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function itemsBatch(string $token, string $catalogId, array $requests, int $retries = 3): array
    {
        $attempt = 0;
        $last = null;

        while ($attempt < $retries) {
            $attempt++;
            try {
                $response = Http::timeout(45)->asForm()->post(
                    "https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$catalogId}/items_batch",
                    [
                        'access_token' => $token,
                        'item_type' => 'PRODUCT_ITEM',
                        'allow_upsert' => 'true',
                        'requests' => json_encode($requests),
                    ]
                );

                $body = $response->json() ?: ['raw' => $response->body()];
                if ($response->successful()) {
                    $handle = $body['handles'][0] ?? null;

                    return [
                        'ok' => true,
                        'handle' => $handle,
                        'body' => $body,
                        'attempt' => $attempt,
                    ];
                }

                $code = (int) ($body['error']['code'] ?? $response->status());
                $message = $body['error']['message'] ?? $response->body();
                $last = ['ok' => false, 'message' => $message, 'body' => $body, 'attempt' => $attempt];

                // Rate limit / transient
                if (in_array($code, [4, 17, 32, 613, 80014], true) || $response->status() === 429) {
                    usleep((int) (pow(2, $attempt) * 250000));
                    continue;
                }

                return $last;
            } catch (\Throwable $e) {
                $last = ['ok' => false, 'message' => $e->getMessage(), 'attempt' => $attempt];
                usleep((int) (pow(2, $attempt) * 250000));
            }
        }

        return $last ?: ['ok' => false, 'message' => 'Meta catalog request failed.'];
    }

    private function extractItemErrors(array $body, string $retailerId): array
    {
        $errors = [];
        foreach ($body['validation_status'] ?? [] as $row) {
            if (($row['retailer_id'] ?? null) !== $retailerId && ($row['retailer_id'] ?? null) !== null) {
                // still collect if only one item
            }
            foreach ($row['errors'] ?? [] as $err) {
                $errors[] = is_array($err) ? ($err['message'] ?? json_encode($err)) : (string) $err;
            }
        }

        return $errors;
    }

    private function upsertRecord(Product $product, array $payload, string $hash, array $local): MetaCatalogProduct
    {
        $record = MetaCatalogProduct::query()->firstOrNew(['product_id' => $product->id]);
        $wasSynced = $record->exists && $record->sync_status === MetaCatalogProduct::STATUS_SYNCED;
        $hashChanged = $record->exists && $record->content_hash && !hash_equals((string) $record->content_hash, $hash);

        $status = $record->sync_status ?: MetaCatalogProduct::STATUS_PENDING;
        if (!empty($local['errors'])) {
            $status = MetaCatalogProduct::STATUS_NEEDS_ATTENTION;
        } elseif ($wasSynced && $hashChanged) {
            $status = MetaCatalogProduct::STATUS_STALE;
        } elseif (!$record->exists) {
            $status = MetaCatalogProduct::STATUS_PENDING;
        }

        $record->fill([
            'retailer_id' => (string) $payload['id'],
            'meta_catalog_id' => $this->catalogId(),
            'content_hash' => $hash,
            'last_payload' => $payload,
            'validation_errors' => $local['errors'] ?? [],
            'validation_warnings' => $local['warnings'] ?? [],
            'sync_status' => $status,
        ]);
        $record->save();

        return $record;
    }

    private function previewFromPayload(Product $product, array $payload, array $local, MetaCatalogProduct $record): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'title' => $payload['title'] ?? $product->name,
            'description' => $payload['description'] ?? '',
            'price' => $payload['price'] ?? null,
            'sale_price' => $payload['sale_price'] ?? null,
            'availability' => $payload['availability'] ?? null,
            'brand' => $payload['brand'] ?? null,
            'category' => $payload['google_product_category'] ?? null,
            'color' => $payload['color'] ?? null,
            'material' => $payload['material'] ?? null,
            'size' => $payload['size'] ?? null,
            'url' => $payload['url'] ?? null,
            'image' => $payload['image_link'] ?? null,
            'gallery' => $payload['additional_image_link'] ?? [],
            'videos' => $product->videos->map(fn ($v) => [
                'provider' => $v->video_provider,
                'link' => $v->link,
                'thumbnail' => $v->thumbnail_url ?: null,
            ])->values()->all(),
            'errors' => $local['errors'] ?? [],
            'warnings' => $local['warnings'] ?? [],
            'sync_status' => $record->sync_status,
            'last_synced_at' => optional($record->last_synced_at)?->toDateTimeString(),
        ];
    }

    private function statusRow(MetaCatalogProduct $row): array
    {
        return [
            'product_id' => $row->product_id,
            'name' => $row->product?->name,
            'sku' => $row->product?->sku,
            'retailer_id' => $row->retailer_id,
            'sync_status' => $row->sync_status,
            'meta_catalog_id' => $row->meta_catalog_id,
            'meta_product_id' => $row->meta_product_id,
            'content_hash' => $row->content_hash,
            'last_error' => $row->last_error,
            'errors' => $row->validation_errors ?: [],
            'warnings' => $row->validation_warnings ?: [],
            'last_synced_at' => optional($row->last_synced_at)?->toDateTimeString(),
            'last_validated_at' => optional($row->last_validated_at)?->toDateTimeString(),
            'batch_handle' => $row->batch_handle,
        ];
    }

    private function cleanDescription(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?: '';

        return trim($text);
    }

    private function contentHash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload));
    }

    private function absolutizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (Str::startsWith($url, '//')) {
            $url = 'https:' . $url;
        }
        if (Str::startsWith($url, '/')) {
            $url = rtrim((string) config('app.url'), '/') . $url;
        }
        if (!Str::startsWith($url, 'http')) {
            return null;
        }
        if (Str::startsWith($url, 'http://')) {
            $url = 'https://' . substr($url, 7);
        }

        return $url;
    }

    private function log(int $productId, string $action, string $status, ?string $message, mixed $response = null): void
    {
        if (!Schema::hasTable('meta_catalog_sync_logs')) {
            return;
        }

        try {
            MetaCatalogSyncLog::query()->create([
                'product_id' => $productId,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'response' => is_array($response) ? $response : ['value' => $response],
            ]);
        } catch (\Throwable $e) {
            Log::warning('meta_catalog_sync_log_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function credentials(): array
    {
        return [$this->token(), $this->catalogId()];
    }

    private function token(): ?string
    {
        $token = Settings::group('site')->get('site_meta_marketing_token')
            ?: Settings::group('site')->get('site_facebook_capi_token');

        return $token ? (string) $token : null;
    }

    private function catalogId(): ?string
    {
        $id = Settings::group('site')->get('site_meta_catalog_id');
        if (!$id) {
            return null;
        }

        return preg_replace('/\D+/', '', (string) $id) ?: null;
    }

    private function currency(): string
    {
        $currencyId = Settings::group('site')->get('site_default_currency');
        $code = \App\Models\Currency::find($currencyId)?->code;

        return $code ?: 'PKR';
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('meta_catalog_products');
    }

    private function emptyDashboard(string $message): array
    {
        return [
            'catalog_id' => $this->catalogId(),
            'configured' => false,
            'message' => $message,
            'totals' => [
                'tracked' => 0,
                'synced' => 0,
                'pending' => 0,
                'failed' => 0,
                'needs_attention' => 0,
                'missing_images' => 0,
                'invalid_landing' => 0,
            ],
            'last_synced_at' => null,
            'csv_url' => url('/feeds/meta-catalog.csv'),
            'recent' => [],
        ];
    }
}
