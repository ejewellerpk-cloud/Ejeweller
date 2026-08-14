<?php

namespace App\Http\PaymentGateways\PaymentRequests;

use Illuminate\Foundation\Http\FormRequest;

class Swich extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'swich_method' => ['nullable', 'in:jazzcash,easypaisa,biller'],
            'swich_mobile' => ['nullable'],
            'msisdn' => ['nullable'],
            'email' => ['nullable'],
            'swich_email' => ['nullable'],
        ];
    }
}
