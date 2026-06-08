<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'    => ['required', 'numeric', 'exists:users,id'],
            'product_id' => ['required', 'numeric', 'exists:products,id'],
            'star'       => ['required', 'numeric', 'min:1', 'max:5'],
            'review'     => ['required', 'string', 'max:5000'],
            'images'     => ['nullable', 'array', 'max:5'],
            'images.*'   => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
