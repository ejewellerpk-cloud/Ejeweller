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
        $required = request()->easypaisa_status == Activity::ENABLE ? 'required' : 'nullable';

        return [
            'easypaisa_client_id' => [$required, 'string'],
            'easypaisa_client_secret' => [$required, 'string'],
            'easypaisa_mode' => [$required, 'string'],
            'easypaisa_status' => ['nullable', 'numeric'],
        ];
    }
}
