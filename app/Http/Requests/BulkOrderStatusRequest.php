<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'      => ['required', 'array', 'min:1'],
            'ids.*'    => ['integer', 'exists:orders,id'],
            'status'   => ['required', 'numeric'],
            'reason'   => ['nullable', 'string', 'max:700'],
        ];
    }
}
