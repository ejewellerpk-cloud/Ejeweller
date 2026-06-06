<?php

namespace App\Http\Controllers\Frontend;


use App\Enums\Status;
use App\Models\Analytic;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Slider;
use App\Models\ThemeSetting;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\PromotionResource;
use App\Http\Resources\SliderResource;

class RootController extends Controller
{
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        $analytics    = Analytic::with('analyticSections')->where(['status' => Status::ACTIVE])->get();
        $themeFavicon = ThemeSetting::where(['key' => 'theme_favicon_logo'])->first();
        $favIcon      = $themeFavicon->faviconLogo;
        $themeLogo    = ThemeSetting::where(['key' => 'theme_logo'])->first()?->logo ?? null;

        // Extract product slug if current route is a product details route
        $seoTitle       = null;
        $seoDescription = null;
        $seoImage       = null;
        $path           = request()->path();
        $isHomepage     = $path === '' || $path === 'home';
        $heroSliders    = [];
        $heroPreloadImage = null;
        $homeCategories = [];
        $homePromotions = [];

        if ($isHomepage) {
            $heroSliders = SliderResource::collection(
                Slider::with('media')
                    ->where('status', Status::ACTIVE)
                    ->orderByDesc('id')
                    ->get()
            )->resolve();

            $heroPreloadImage = $heroSliders[0]['image'] ?? null;

            $homeCategories = ProductCategoryResource::collection(
                ProductCategory::with('parent_category', 'media')
                    ->where('status', Status::ACTIVE)
                    ->whereNull('parent_id')
                    ->orderBy('id', 'asc')
                    ->get()
            )->resolve();

            $homePromotions = PromotionResource::collection(
                Promotion::where('status', Status::ACTIVE)
                    ->orderBy('id', 'asc')
                    ->get()
            )->resolve();
        }

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
            'analytics'        => $analytics,
            'favicon'          => $favIcon,
            'themeLogo'        => $themeLogo,
            'seoTitle'         => $seoTitle,
            'seoDescription'   => $seoDescription,
            'seoImage'         => $seoImage,
            'isHomepage'       => $isHomepage,
            'heroSliders'      => $heroSliders,
            'heroPreloadImage' => $heroPreloadImage,
            'homeCategories'   => $homeCategories,
            'homePromotions'   => $homePromotions,
        ]);
    }
}
