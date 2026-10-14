<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkPaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'            => ['required', 'array', 'min:1'],
            'ids.*'          => ['integer', 'exists:orders,id'],
            'payment_status' => ['required', 'numeric'],
        ];
    }
}
