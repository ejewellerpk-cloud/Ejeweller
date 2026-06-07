<?php

namespace App\Http\Resources;


use App\Enums\Ask;
use Carbon\Carbon;
use App\Enums\Activity;
use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAllVariationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $discount = (double) ($this->product?->discount ?? 0);
        $isOffer = AppLibrary::isProductOfferActive(
            $discount,
            $this->product?->offer_start_date,
            $this->product?->offer_end_date
        );
        $offerPrice = AppLibrary::productOfferPrice(
            (float) $this->price,
            $discount,
            $this->product?->offer_start_date,
            $this->product?->offer_end_date
        );

        return [
            'id'                            => $this->id,
            'product_attribute_id'          => $this->product_attribute_id,
            'product_attribute_option_id'   => $this->product_attribute_option_id,
            'product_attribute_name'        => $this->productAttribute?->name,
            'product_attribute_option_name' => $this->productAttributeOption?->name,
            'price'                         => AppLibrary::convertAmountFormat($offerPrice),
            'currency_price'                => AppLibrary::currencyAmountFormat($offerPrice),
            'old_price'                     => AppLibrary::convertAmountFormat($this->price),
            'old_currency_price'            => AppLibrary::currencyAmountFormat($this->price),
            'discount'                      => $isOffer ? AppLibrary::convertAmountFormat(($this->price / 100) * $discount) : 0,
            'discount_percentage'           => $isOffer ? (float) $discount : 0,
            'is_offer'                      => $isOffer,
            'sku'                           => $this->sku,
            'image'                         => $this->image,
            'stock'                         => $this->product?->show_stock_out == Activity::DISABLE ? (int) $this->stock_items_sum_quantity : 0,
            'children'                      => ProductAllVariationResource::collection($this->children),
        ];
    }
}