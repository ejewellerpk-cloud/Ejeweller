<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminDeleteFilteredReviewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirm'      => ['required', 'accepted'],
            'user_id'      => ['nullable', 'numeric', 'exists:users,id'],
            'product_id'   => ['nullable', 'numeric', 'exists:products,id'],
            'star'         => ['nullable', 'numeric', 'min:1', 'max:5'],
            'status'       => ['nullable', 'numeric'],
            'images_count' => ['nullable', 'string'],
        ];
    }
}
