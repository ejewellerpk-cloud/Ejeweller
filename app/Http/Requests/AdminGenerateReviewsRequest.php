<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminGenerateReviewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'numeric', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:50'],
            'images'     => ['nullable', 'array', 'max:10'],
            'images.*'   => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
