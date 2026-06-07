<?php

namespace App\Http\Resources;


use App\Enums\Activity;
use App\Enums\Ask;
use App\Libraries\AppLibrary;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleProductVariationResource extends JsonResource
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
        $isOfferActive = AppLibrary::isProductOfferActive(
            $discount,
            $this->product?->offer_start_date,
            $this->product?->offer_end_date
        );
        $discountedPrice = AppLibrary::productOfferPrice(
            (float) $this->price,
            $discount,
            $this->product?->offer_start_date,
            $this->product?->offer_end_date
        );
        return [
            'id'                            => $this->id,
            'product_attribute_id'          => (int) $this->product_attribute_id,
            'product_attribute_option_id'   => (int) $this->product_attribute_option_id,
            'product_attribute_name'        => $this->productAttribute?->name,
            'product_attribute_option_name' => $this->productAttributeOption?->name,
            'price'                         => AppLibrary::convertAmountFormat($discountedPrice),
            'currency_price'                => AppLibrary::currencyAmountFormat($discountedPrice),
            'old_price'                     => AppLibrary::convertAmountFormat($this->price),
            'old_currency_price'            => AppLibrary::currencyAmountFormat($this->price),
            'discount'                      => $isOfferActive ? AppLibrary::convertAmountFormat(($this->price / 100) * $this->product->discount) : 0,
            'discount_percentage'           => $isOfferActive ? (float) $discount : 0,
            'is_offer'                      => $isOfferActive,
            'sku'                           => $this->sku,
            'image'                         => $this->image,
            'stock'                         => $this->product?->show_stock_out == Activity::DISABLE ? (int) $this->stock_items_sum_quantity : 0,
            "maximum_purchase_quantity"     => $this->product?->maximum_purchase_quantity,
        ];
    }
}
