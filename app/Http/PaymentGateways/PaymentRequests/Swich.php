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
            'swich_method' => ['required', 'in:jazzcash,easypaisa,biller'],
            'msisdn' => ['required', 'string', 'regex:/^(03\d{9}|92\d{10}|\+92\d{10}|3\d{9})$/'],
            'email' => ['required', 'email'],
        ];
    }
}
