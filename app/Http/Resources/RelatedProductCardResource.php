<?php

namespace App\Http\Resources;

use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class RelatedProductCardResource extends JsonResource
{
    public function toArray($request): array
    {
        $hasVariations = (int) ($this->variations_count ?? 0) > 0;
        $price = $hasVariations ? $this->variation_price : $this->selling_price;
        $discount = (double) $this->discount;
        $isOffer = AppLibrary::isProductOfferActive($discount, $this->offer_start_date, $this->offer_end_date);
        $offerPrice = AppLibrary::productOfferPrice((float) $price, $discount, $this->offer_start_date, $this->offer_end_date);

        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'slug'                 => $this->slug,
            'cover'                => $this->cover,
            'is_offer'             => $isOffer,
            'discount_percentage'  => $isOffer ? (float) $discount : 0,
            'currency_price'       => AppLibrary::currencyAmountFormat($offerPrice),
            'discounted_price'     => AppLibrary::currencyAmountFormat($offerPrice),
            'old_currency_price'   => $isOffer ? AppLibrary::currencyAmountFormat($price) : null,
            'rating_star_average'  => (int) ($this->rating_star_count ?? 0) > 0
                ? round((float) $this->rating_star / (int) $this->rating_star_count, 1)
                : null,
        ];
    }
}
