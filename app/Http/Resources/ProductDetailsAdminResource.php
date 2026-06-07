<?php

namespace App\Http\Resources;


use App\Enums\Ask;
use Carbon\Carbon;
use App\Enums\Activity;
use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailsAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $price = count($this?->variations) > 0 ? $this->variation_price : $this->selling_price;
        $discount = (double) $this->discount;
        $isOffer = AppLibrary::isProductOfferActive($discount, $this->offer_start_date, $this->offer_end_date);
        $offerPrice = AppLibrary::productOfferPrice((float) $price, $discount, $this->offer_start_date, $this->offer_end_date);
        return [
            "id"                           => $this->id,
            "use_random_sale"              => (int)$this->use_random_sale,
            "is_show_viewers"              => (int)$this->is_show_viewers,
            "actual_sales"                 => (int)abs($this->productOrders()->sum('quantity')),
            "name"                         => $this->name,
            "sku"                          => $this->sku,
            "category"                     => $this->category?->name,
            "brand"                        => $this->brand?->name,
            "barcode"                      => $this->barcode?->name,
            "tax"                          => AppLibrary::taxString($this?->taxes),
            "flat_buying_price"            => AppLibrary::flatAmountFormat($this->buying_price),
            "flat_selling_price"           => AppLibrary::flatAmountFormat($this->selling_price),
            "maximum_purchase_quantity"    => $this->maximum_purchase_quantity,
            "low_stock_quantity_warning"   => $this->low_stock_quantity_warning,
            "weight"                       => $this->weight,
            "warranty"                   => $this->warranty,
            "unit"                         => $this->unit?->name,
            "can_purchasable"              => $this->can_purchasable,
            "show_stock_out"               => $this->show_stock_out,
            "refundable"                   => $this->refundable,
            "status"                       => $this->status,
            "tags"                         => AppLibrary::tagString($this?->tags),
            "description"                  => $this->description === null ? '' : $this->description,
            "preview"                      => $this->preview,
            "image"                        => $this->preview,
            "images"                       => $this->previews,
            "image_items"                  => $this->image_items,
            "shipping_and_return"          => $this->shipping_and_return === null ? '' : $this->shipping_and_return,
            "add_to_flash_sale"            => $this->add_to_flash_sale,
            "offer_start_date"             => $this->offer_start_date,
            "offer_end_date"               => $this->offer_end_date,
            "shipping_type"                => $this->shipping_type,
            "shipping_cost"                => AppLibrary::flatAmountFormat($this->shipping_cost),
            "is_product_quantity_multiply" => $this->is_product_quantity_multiply,
            'category_slug'                => $this->category?->slug,
            'price'                        => AppLibrary::convertAmountFormat($offerPrice),
            'currency_price'               => AppLibrary::currencyAmountFormat($offerPrice),
            'old_price'                    => AppLibrary::convertAmountFormat($price),
            'old_currency_price'           => AppLibrary::currencyAmountFormat($price),
            'discount'                     => $isOffer ? AppLibrary::convertAmountFormat(($price / 100) * $discount) : 0,
            'discount_percentage'          => $isOffer ? AppLibrary::convertAmountFormat($discount) : 0,
            'flash_sale'                   => $this->add_to_flash_sale == Ask::YES,
            'is_offer'                     => $isOffer,
            'rating_star'                  => $this->rating_star,
            'rating_star_count'            => $this->rating_star_count,
            'stock'                        => $this->show_stock_out == Activity::DISABLE ? (int) $this->stock_items_sum_quantity : 0,
            'taxes'                        => SimpleTaxResource::collection($this->taxes),
            'thumb'                        => $this->thumb,
            "barcode_image"                => $this->barcodeImage,
        ];
    }
}
