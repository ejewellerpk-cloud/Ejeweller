<?php

namespace App\Support\MediaLibrary;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Slider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class OrganizedMediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media) . '/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . '/responsive-images/';
    }

    protected function getBasePath(Media $media): string
    {
        $prefix = trim((string) config('media-library.prefix', ''), '/');
        $base = $this->resolveOrganizedPath($media);

        if ($prefix !== '') {
            return $prefix . '/' . $base;
        }

        return $base;
    }

    protected function resolveOrganizedPath(Media $media): string
    {
        $modelType = $media->model_type;
        $collection = $media->collection_name;
        $modelId = $media->model_id;

        if ($modelType === Product::class) {
            if ($collection === 'product') {
                return 'media/products/' . $modelId;
            }

            if ($collection === 'product-barcode') {
                return 'media/barcodes/' . $modelId;
            }
        }

        if ($modelType === ProductCategory::class && $collection === 'product-category') {
            return 'media/categories/' . $modelId;
        }

        if ($modelType === ProductBrand::class && $collection === 'product-brand') {
            return 'media/categories/brands/' . $modelId;
        }

        if ($modelType === Promotion::class && $collection === 'promotion') {
            return 'media/promotions/' . $modelId;
        }

        if ($modelType === Slider::class && $collection === 'slider') {
            return 'media/sliders/' . $modelId;
        }

        return 'media/miscellaneous/' . $media->getKey();
    }
}
