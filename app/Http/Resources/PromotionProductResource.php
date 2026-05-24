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
        $discount = (double) ($this->product?->discount ?? 0);
        $isOffer = AppLibrary::isProductOfferActive(
            $discount,
            $this->product?->offer_start_date,
            $this->product?->offer_end_date
        );
        $offerPrice = AppLibrary::productOfferPrice(
            (float) $price,
            $discount,
            $this->product?->offer_start_date,
            $this->product?->offer_end_date
        );
        return [
            'id'                                   => $this->product_id,
            'name'                                 => optional($this->product)->name,
            'discounted_price'                     => AppLibrary::currencyAmountFormat($offerPrice),
            'price'                                => (double) $offerPrice,
            'old_price'                            => (double) $price,
            'maximum_purchase_quantity'            => (int) $this->product?->maximum_purchase_quantity,
            'taxes'                                => ProductTaxResource::collection($this->product?->taxes),
            'shipping'                             => [
                'shipping_type'  => $this->product?->shipping_type,
                'shipping_cost'  => (double) $this->product?->shipping_cost,
                'is_product_quantity_multiply' => $this->product?->is_product_quantity_multiply
            ],
            'currency_price'                       => AppLibrary::currencyAmountFormat($price),
            'flash_sale'                           => $this->product?->add_to_flash_sale == Ask::YES,
            'is_offer'                             => $isOffer,
            'is_last_day_of_sale'                  => $isOffer && $this->product?->offer_end_date ? Carbon::parse($this->product->offer_end_date)->isToday() : false,
            'discount'                             => $discount,
            'stock'                                => $this->product?->stock,
            'slug'                                 => $this->product?->slug,
            'cover'                                => $this->product?->cover,
            'previews'                             => $this->product?->previews,
            'variation_count'                      => (int) count($this->product?->variations),
            'rating_star'                          => $this->product?->rating_star,
            'rating_star_count'                    => (int) $this->product?->rating_star_count,
            'wishlist'                             => (bool) $this->product?->wishlist,
            'videos'                               => ProductVideoResource::collection($this->product?->videos),
            'in_baskets'                           => (int) ($this->product?->cart_trackers_count ?? $this->product?->cartTrackers()->count()),
            'bought_last_24_hours'                 => (int) abs(
                $this->product?->product_orders_last_day_sum_quantity
                    ?? $this->product?->productOrders()->where('created_at', '>=', now()->subDay())->sum('quantity')
            ),
        ];
    }
}