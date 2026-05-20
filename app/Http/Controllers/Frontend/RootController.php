<?php

namespace App\Http\Controllers\Frontend;


use App\Enums\Status;
use App\Models\Analytic;
use App\Models\ThemeSetting;
use App\Http\Controllers\Controller;

class RootController extends Controller
{
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        $analytics    = Analytic::with('analyticSections')->where(['status' => Status::ACTIVE])->get();
        $themeFavicon = ThemeSetting::where(['key' => 'theme_favicon_logo'])->first();
        $favIcon      = $themeFavicon->faviconLogo;

        // Extract product slug if current route is a product details route
        $seoTitle       = null;
        $seoDescription = null;
        $seoImage       = null;
        $path           = request()->path();

        if (preg_match('/^product\/([^\/]+)$/', $path, $matches)) {
            $slug    = $matches[1];
            $product = \App\Models\Product::where('slug', $slug)->first();
            if ($product) {
                $seoTitle       = $product->name;
                $seoDescription = !blank($product->description) ? strip_tags(substr($product->description, 0, 160)) : '';
                
                // Build robust absolute URL using dynamic current domain
                $imagePath = '';
                if (!empty($product->getFirstMediaUrl('product'))) {
                    $mediaUrl = $product->getFirstMediaUrl('product');
                    $parsedUrl = parse_url($mediaUrl);
                    $imagePath = $parsedUrl['path'] ?? '';
                }
                
                if (!empty($imagePath)) {
                    $seoImage = request()->getSchemeAndHttpHost() . $imagePath;
                } else {
                    $seoImage = request()->getSchemeAndHttpHost() . '/images/default/product/thumb.png';
                }
            }
        }

        return view('master', [
            'analytics'      => $analytics, 
            'favicon'        => $favIcon,
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage'       => $seoImage
        ]);
    }
}
