<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductBrand;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $base = rtrim(config('app.url') ?: request()->getSchemeAndHttpHost(), '/');

        $urls = [
            ['loc' => $base . '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => $base . '/products', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => $base . '/offers', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => $base . '/flash-sale', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => $base . '/track-order', 'changefreq' => 'monthly', 'priority' => '0.4'],
        ];

        ProductCategory::query()
            ->where('status', Status::ACTIVE)
            ->orderBy('id')
            ->limit(2000)
            ->get(['slug', 'updated_at'])
            ->each(function ($category) use (&$urls, $base) {
                if (!$category->slug) {
                    return;
                }
                $urls[] = [
                    'loc'        => $base . '/product-category/' . $category->slug,
                    'lastmod'    => optional($category->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '0.7',
                ];
            });

        ProductBrand::query()
            ->where('status', Status::ACTIVE)
            ->orderBy('id')
            ->limit(1000)
            ->get(['slug', 'updated_at'])
            ->each(function ($brand) use (&$urls, $base) {
                if (!$brand->slug) {
                    return;
                }
                $urls[] = [
                    'loc'        => $base . '/product-brand/' . $brand->slug,
                    'lastmod'    => optional($brand->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '0.6',
                ];
            });

        Product::query()
            ->where('status', Status::ACTIVE)
            ->orderByDesc('id')
            ->limit(10000)
            ->get(['slug', 'updated_at'])
            ->each(function ($product) use (&$urls, $base) {
                if (!$product->slug) {
                    return;
                }
                $urls[] = [
                    'loc'        => $base . '/product/' . $product->slug,
                    'lastmod'    => optional($product->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>' . e($url['loc']) . '</loc>';
            if (!empty($url['lastmod'])) {
                $xml .= '<lastmod>' . e($url['lastmod']) . '</lastmod>';
            }
            $xml .= '<changefreq>' . e($url['changefreq']) . '</changefreq>';
            $xml .= '<priority>' . e($url['priority']) . '</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
