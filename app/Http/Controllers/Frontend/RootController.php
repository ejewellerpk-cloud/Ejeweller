<?php

namespace App\Http\Controllers\Frontend;


use App\Enums\Status;
use App\Models\Analytic;
use App\Models\Product;
use App\Models\Slider;
use App\Models\ThemeSetting;
use App\Http\Controllers\Controller;
use App\Http\Resources\SliderResource;

class RootController extends Controller
{
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        $analytics    = Analytic::with('analyticSections')->where(['status' => Status::ACTIVE])->get();
        $themeFavicon = ThemeSetting::where(['key' => 'theme_favicon_logo'])->first();
        $favIcon      = $themeFavicon->faviconLogo;

        $seoTitle       = null;
        $seoDescription = null;
        $seoImage       = null;
        $seoKeywords    = null;
        $jsonLd         = null;
        $canonicalUrl   = url()->current();
        $path           = request()->path();
        $isHomepage     = $path === '' || $path === 'home';
        $heroSliders    = [];
        $heroPreloadImage = null;

        if ($isHomepage) {
            $heroSliders = SliderResource::collection(
                Slider::with('media')
                    ->where('status', Status::ACTIVE)
                    ->orderByDesc('id')
                    ->get()
            )->resolve();

            $heroPreloadImage = $heroSliders[0]['mobile_image']
                ?? $heroSliders[0]['image']
                ?? null;
        }

        if (preg_match('/^product\/([^\/]+)$/', $path, $matches)) {
            $slug    = $matches[1];
            $product = Product::with(['seo', 'brand', 'category'])
                ->withReviewRating()
                ->where('slug', $slug)
                ->first();

            if ($product) {
                $seoTitle       = $product->seo?->title ?: $product->name;
                $seoDescription = $product->seo?->description
                    ?: (!blank($product->description) ? strip_tags(substr($product->description, 0, 160)) : '');
                $seoKeywords    = $product->seo?->meta_keyword;

                $imagePath = '';
                if (!empty($product->getFirstMediaUrl('product'))) {
                    $mediaUrl  = $product->getFirstMediaUrl('product');
                    $parsedUrl = parse_url($mediaUrl);
                    $imagePath = $parsedUrl['path'] ?? '';
                }

                if (!empty($imagePath)) {
                    $seoImage = request()->getSchemeAndHttpHost() . $imagePath;
                } else {
                    $seoImage = request()->getSchemeAndHttpHost() . '/images/default/product/thumb.png';
                }

                $price = (float) ($product->selling_price ?: $product->variation_price ?: 0);
                $ratingCount = (int) ($product->rating_star_count ?? 0);
                $ratingValue = $ratingCount > 0
                    ? round(((float) $product->rating_star) / $ratingCount, 1)
                    : null;

                $jsonLd = [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'Product',
                    'name'        => $product->name,
                    'description' => $seoDescription,
                    'image'       => [$seoImage],
                    'sku'         => $product->sku,
                    'brand'       => $product->brand?->name ? [
                        '@type' => 'Brand',
                        'name'  => $product->brand->name,
                    ] : null,
                    'offers'      => [
                        '@type'         => 'Offer',
                        'url'           => $canonicalUrl,
                        'priceCurrency' => env('CURRENCY', 'PKR'),
                        'price'         => number_format($price, (int) (env('CURRENCY_DECIMAL_POINT') ?: 2), '.', ''),
                        'availability'  => 'https://schema.org/InStock',
                    ],
                ];

                if ($ratingValue !== null && $ratingCount > 0) {
                    $jsonLd['aggregateRating'] = [
                        '@type'       => 'AggregateRating',
                        'ratingValue' => $ratingValue,
                        'reviewCount' => $ratingCount,
                    ];
                }

                $jsonLd = array_filter($jsonLd, fn ($v) => $v !== null);
            }
        }

        return view('master', [
            'analytics'        => $analytics,
            'favicon'          => $favIcon,
            'seoTitle'         => $seoTitle,
            'seoDescription'   => $seoDescription,
            'seoImage'         => $seoImage,
            'seoKeywords'      => $seoKeywords,
            'canonicalUrl'     => $canonicalUrl,
            'jsonLd'           => $jsonLd,
            'isHomepage'       => $isHomepage,
            'heroSliders'      => $heroSliders,
            'heroPreloadImage' => $heroPreloadImage,
        ]);
    }
}
