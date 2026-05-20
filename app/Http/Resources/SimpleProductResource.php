<?php

namespace App\Http\Resources;


use App\Enums\Ask;
use App\Libraries\AppLibrary;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $price = count($this->variations) > 0 ? $this->variation_price : $this->selling_price;
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'currency_price'    => AppLibrary::currencyAmountFormat($price),
            'cover'             => $this->cover,
            'previews'          => $this->previews,
            'flash_sale'        => $this->add_to_flash_sale == Ask::YES,
            'is_offer'          => AppLibrary::isBetweenDate($this->offer_start_date, $this->offer_end_date),
            'discounted_price'  => AppLibrary::currencyAmountFormat($price - (($price / 100) * $this->discount)),
            'price'             => (double) ($price - (($price / 100) * $this->discount)),
            'old_price'         => (double) $price,
            'maximum_purchase_quantity' => (int) $this->maximum_purchase_quantity,
            'taxes'             => ProductTaxResource::collection($this->taxes),
            'shipping'          => [
                'shipping_type'  => $this->shipping_type,
                'shipping_cost'  => (double) $this->shipping_cost,
                'is_product_quantity_multiply' => $this->is_product_quantity_multiply
            ],
            'rating_star'       => $this->rating_star,
            'rating_star_count' => (int) $this->rating_star_count,
            'wishlist'          => (bool)$this->wishlist,
            'variation_count'   => (int) count($this->variations),
            'videos'            => ProductVideoResource::collection($this->videos),
        ];
    }
}
