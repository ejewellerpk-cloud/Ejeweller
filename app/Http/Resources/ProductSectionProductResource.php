<?php

namespace App\Http\Resources;


use App\Enums\Ask;
use Carbon\Carbon;
use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSectionProductResource extends JsonResource
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
            'id'                                        => $this->product_id,
            'name'                                      => optional($this->product)->name,
            'productSection_id'                         => $this->product_section_id,
            'productSection_product_id'                 => $this->product_id,
            'productSection_name'                       => optional($this->productSection)->name,
            'productSection_product_name'               => optional($this->product)->name,
            "productSection_product_flat_selling_price" => AppLibrary::flatAmountFormat($this->product?->selling_price),
            'productSection_product_status'             => optional($this->product)->status,
            'currency_price'                            => AppLibrary::currencyAmountFormat($price),
            'flash_sale'                                => $this->product?->add_to_flash_sale == Ask::YES,
            'is_offer'                                  => AppLibrary::isBetweenDate($this->product?->offer_start_date, $this->product?->offer_end_date),
            'discounted_price'                          => AppLibrary::currencyAmountFormat($price - (($price / 100) * $this->product?->discount)),
            'price'                                     => (double) ($price - (($price / 100) * $this->product?->discount)),
            'old_price'                                 => (double) $price,
            'maximum_purchase_quantity'                 => (int) $this->product?->maximum_purchase_quantity,
            'taxes'                                     => ProductTaxResource::collection($this->product?->taxes),
            'shipping'                                  => [
                'shipping_type'  => $this->product?->shipping_type,
                'shipping_cost'  => (double) $this->product?->shipping_cost,
                'is_product_quantity_multiply' => $this->product?->is_product_quantity_multiply
            ],
            'stock'                                     => $this->product?->stock,
            'slug'                                      => $this->product?->slug,
            'cover'                                     => $this->product?->cover,
            'previews'                                  => $this->product?->previews,
            'variation_count'                           => (int) count($this->product?->variations),
            'rating_star'                               => $this->product?->rating_star,
            'rating_star_count'                         => (int) $this->product?->rating_star_count,
            'wishlist'                                  => (bool) $this->product?->wishlist,
            'videos'                                    => ProductVideoResource::collection($this->product?->videos),
        ];
    }
}
