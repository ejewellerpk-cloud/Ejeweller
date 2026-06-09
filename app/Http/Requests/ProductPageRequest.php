<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_page_related_status'     => ['required', 'numeric', 'in:5,10'],
            'product_page_related_autoscroll' => ['required', 'numeric', 'in:5,10'],
            'product_page_related_speed'      => ['required', 'numeric', 'min:2000', 'max:10000'],
            'product_page_related_touch'      => ['required', 'numeric', 'in:5,10'],
            'product_page_related_direction'  => ['required', 'string', 'in:ltr,rtl'],
        ];
    }
}
