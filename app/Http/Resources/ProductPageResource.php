<?php

namespace App\Http\Resources;

use App\Enums\Activity;
use App\Services\ProductPageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_page_related_status'     => isset($this->resource['product_page_related_status'])
                ? (int) $this->resource['product_page_related_status']
                : Activity::ENABLE,
            'product_page_related_autoscroll' => isset($this->resource['product_page_related_autoscroll'])
                ? (int) $this->resource['product_page_related_autoscroll']
                : Activity::ENABLE,
            'product_page_related_speed'      => isset($this->resource['product_page_related_speed'])
                ? (int) $this->resource['product_page_related_speed']
                : ProductPageService::DEFAULT_SCROLL_SPEED,
            'product_page_related_touch'      => isset($this->resource['product_page_related_touch'])
                ? (int) $this->resource['product_page_related_touch']
                : Activity::ENABLE,
            'product_page_related_direction'  => $this->resource['product_page_related_direction'] ?? 'rtl',
        ];
    }
}
