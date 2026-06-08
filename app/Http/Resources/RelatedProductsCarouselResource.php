<?php

namespace App\Http\Resources;

use App\Enums\Activity;
use App\Services\RelatedProductsCarouselService;
use Illuminate\Http\Resources\Json\JsonResource;

class RelatedProductsCarouselResource extends JsonResource
{
    public array $info;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->info = $info;
    }

    public function toArray($request): array
    {
        return [
            'related_products_carousel_status' => isset($this->info['related_products_carousel_status'])
                ? (int) $this->info['related_products_carousel_status']
                : Activity::ENABLE,
            'related_products_carousel_speed' => isset($this->info['related_products_carousel_speed'])
                ? (int) $this->info['related_products_carousel_speed']
                : RelatedProductsCarouselService::DEFAULT_AUTOPLAY_DELAY,
        ];
    }
}
