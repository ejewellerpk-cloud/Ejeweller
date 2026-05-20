<?php

namespace App\Http\Resources;


use App\Enums\Ask;
use Carbon\Carbon;
use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionProductResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */

    public function toArray($request): array
    {
        $price = count($this->product?->variations) > 0 ? $this->product?->variation_price : $this->product?->selling_price;
        return [
            'id'                                   => $this->product_id,
            'name'                                 => optional($this->product)->name,
            'discounted_price'                     => AppLibrary::currencyAmountFormat($price - (($price / 100) * $this->product?->discount)),
            'price'                                => (double) ($price - (($price / 100) * $this->product?->discount)),
            'old_price'                            => (double) $price,
            'maximum_purchase_quantity'            => (int) $this->product?->maximum_purchase_quantity,
            'taxes'                                => ProductTaxResource::collection($this->product?->taxes),
            'shipping'                             => [
                'shipping_type'  => $this->product?->shipping_type,
                'shipping_cost'  => (double) $this->product?->shipping_cost,
                'is_product_quantity_multiply' => $this->product?->is_product_quantity_multiply
            ],
            'stock'                                => $this->product?->stock,
            'slug'                                 => $this->product?->slug,
            'cover'                                => $this->product?->cover,
            'variation_count'                      => (int) count($this->product?->variations),
        ];
    }
}