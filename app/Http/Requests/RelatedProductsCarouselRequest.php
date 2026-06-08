<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelatedProductsCarouselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'related_products_carousel_status' => ['required', 'numeric', 'in:5,10'],
            'related_products_carousel_speed'  => ['required', 'numeric', 'min:2000', 'max:10000'],
        ];
    }
}
