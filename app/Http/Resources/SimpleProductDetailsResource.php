<?php

namespace App\Http\Resources;


use App\Enums\Activity;
use App\Enums\Ask;
use App\Libraries\AppLibrary;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleProductDetailsResource extends JsonResource
{

    public function toArray($request): array
    {
        $price = count($this->variations) > 0 ? $this->variation_price : $this->selling_price;
        $discount = (double) $this->discount;
        $isOffer = AppLibrary::isProductOfferActive($discount, $this->offer_start_date, $this->offer_end_date);
        $offerPrice = AppLibrary::productOfferPrice((float) $price, $discount, $this->offer_start_date, $this->offer_end_date);

        return [
            'id'                        => $this->id,
            'use_random_sale'           => (int)$this->use_random_sale,
            'is_show_viewers'           => (int)$this->is_show_viewers,
            'actual_sales'              => (int)abs($this->productOrders()->sum('quantity')),
            'bought_last_24_hours'      => (int) abs(
                $this->product_orders_last_day_sum_quantity
                    ?? $this->productOrders()->where('created_at', '>=', now()->subDay())->sum('quantity')
            ),
            'in_baskets'                => (int) ($this->cart_trackers_count ?? $this->cartTrackers()->count()),
            'name'                      => $this->name,
            'slug'                      => $this->slug,
            'price'                     => AppLibrary::convertAmountFormat($offerPrice),
            'currency_price'            => AppLibrary::currencyAmountFormat($offerPrice),
            'old_price'                 => AppLibrary::convertAmountFormat($price),
            'old_currency_price'        => AppLibrary::currencyAmountFormat($price),
            'discount'                  => $isOffer ? AppLibrary::convertAmountFormat(($price / 100) * $discount) : 0,
            'discount_percentage'       => $isOffer ? AppLibrary::convertAmountFormat($discount) : 0,
            'flash_sale'                => $this->add_to_flash_sale == Ask::YES,
            'offer_end_date'            => $this->offer_end_date,
            'is_offer'                  => $isOffer,
            'is_last_day_of_sale'       => $isOffer && $this->offer_end_date ? Carbon::parse($this->offer_end_date)->isToday() : false,
            'rating_star'               => $this->rating_star,
            'rating_star_count'         => (int) $this->rating_star_count,
            'image'                     => $this->cover,
            'images'                    => $this->previews,
            'taxes'                     => SimpleTaxResource::collection($this->taxes),
            'reviews'                   => ProductReviewResource::collection($this->reviews),
            'videos'                    => ProductVideoResource::collection($this->videos),
            'seo'                       => new ProductSeoResource($this->seo),
            'wishlist'                  => (bool)$this->wishlist,
            'details'                   => $this->description,
            'shipping_and_return'       => $this->shipping_and_return,
            'category_slug'             => $this->category?->slug,
            'unit'                      => $this->unit?->name,
            'stock'                     => $this->show_stock_out == Activity::DISABLE ? ($this->can_purchasable == Ask::NO ? (int)env('NON_PURCHASE_QUANTITY') : (int)$this->stock_items_sum_quantity) : 0,
            'sku'                       => $this->sku,
            "maximum_purchase_quantity" => $this->maximum_purchase_quantity,
            'shipping'                  => [
                'shipping_type'                => $this->shipping_type,
                'shipping_cost'                => $this->shipping_cost,
                'is_product_quantity_multiply' => $this->is_product_quantity_multiply
            ]
        ];
    }
}
