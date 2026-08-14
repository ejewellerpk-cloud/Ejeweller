<?php

namespace App\Http\PaymentGateways\PaymentRequests;

use App\Http\PaymentGateways\Gateways\Swich as SwichGateway;
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
            'msisdn' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (SwichGateway::normalizeMsisdn((string) $value) === '') {
                        $fail(trans('all.message.swich_msisdn_required'));
                    }
                },
            ],
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'msisdn.required' => trans('all.message.swich_msisdn_required'),
            'swich_method.required' => trans('all.message.swich_method_required'),
            'swich_method.in' => trans('all.message.swich_method_required'),
            'email.required' => trans('all.message.swich_email_required'),
            'email.email' => trans('all.message.swich_email_required'),
        ];
    }
}
