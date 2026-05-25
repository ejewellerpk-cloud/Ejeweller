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
        $discount = (double) $this->discount;
        $isOffer = AppLibrary::isProductOfferActive($discount, $this->offer_start_date, $this->offer_end_date);
        $offerPrice = AppLibrary::productOfferPrice((float) $price, $discount, $this->offer_start_date, $this->offer_end_date);
        return [
            'id'                => $this->id,
            'created_at'        => $this->created_at ? $this->created_at->toIso8601String() : null,
            'use_random_sale'   => (int)$this->use_random_sale,
            'actual_sales'      => (int)abs($this->productOrders()->sum('quantity')),
            'in_baskets'            => (int) ($this->cart_trackers_count ?? $this->cartTrackers()->count()),
            'bought_last_24_hours'  => (int) abs(
                $this->product_orders_last_day_sum_quantity
                    ?? $this->productOrders()->where('created_at', '>=', now()->subDay())->sum('quantity')
            ),
            'name'              => $this->name,
            'slug'              => $this->slug,
            'currency_price'    => AppLibrary::currencyAmountFormat($price),
            'cover'             => $this->cover,
            'previews'          => $this->previews,
            'flash_sale'        => $this->add_to_flash_sale == Ask::YES,
            'is_offer'          => $isOffer,
            'is_last_day_of_sale'=> $isOffer && $this->offer_end_date ? Carbon::parse($this->offer_end_date)->isToday() : false,
            'discount'              => (double) $discount,
            'discount_percentage'   => $isOffer ? (float) $discount : 0,
            'discounted_price'      => AppLibrary::currencyAmountFormat($offerPrice),
            'price'             => (double) $offerPrice,
            'old_price'         => (double) $price,
            'stock'             => (int) $this->stock,
            'maximum_purchase_quantity' => (int) $this->maximum_purchase_quantity,
            'taxes'             => ProductTaxResource::collection($this->taxes),
            'shipping'          => [
                'shipping_type'  => $this->shipping_type,
                'shipping_cost'  => (double) $this->shipping_cost,
                'is_product_quantity_multiply' => $this->is_product_quantity_multiply
            ],
            'rating_star'         => $this->rating_star,
            'rating_star_count'   => (int) $this->rating_star_count,
            'rating_star_average' => (int) $this->rating_star_count > 0
                ? round((float) $this->rating_star / (int) $this->rating_star_count, 1)
                : null,
            'wishlist'          => (bool)$this->wishlist,
            'variation_count'   => (int) count($this->variations),
            'videos'            => ProductVideoResource::collection($this->videos),
        ];
    }
}
