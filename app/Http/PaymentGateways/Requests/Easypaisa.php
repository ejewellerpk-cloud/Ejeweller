<?php

namespace App\Http\PaymentGateways\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class Easypaisa extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (request()->easypaisa_status == Activity::ENABLE) {
            return [
                'easypaisa_store_id'   => ['required', 'string'],
                'easypaisa_hash_key'   => ['required', 'string'],
                'easypaisa_username'   => ['required', 'string'],
                'easypaisa_password'   => ['required', 'string'],
                'easypaisa_mode'       => ['required', 'string'],
                'easypaisa_status'     => ['nullable', 'numeric'],
            ];
        }

        return [
            'easypaisa_store_id'   => ['nullable', 'string'],
            'easypaisa_hash_key'   => ['nullable', 'string'],
            'easypaisa_username'   => ['nullable', 'string'],
            'easypaisa_password'   => ['nullable', 'string'],
            'easypaisa_mode'       => ['nullable', 'string'],
            'easypaisa_status'     => ['nullable', 'numeric'],
        ];
    }
}
