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
            'product_id'     => ['required', 'numeric', 'exists:products,id'],
            'quantity'       => ['required', 'integer', 'min:1'],
            'images'         => ['nullable', 'array'],
            'images.*'       => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'media_token'    => ['nullable', 'string', 'uuid'],
            'cleanup_media'  => ['nullable', 'boolean'],
        ];
    }
}
