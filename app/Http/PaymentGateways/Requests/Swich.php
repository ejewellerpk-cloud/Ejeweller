<?php

namespace App\Http\PaymentGateways\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class Swich extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = request()->swich_status == Activity::ENABLE ? 'required' : 'nullable';

        return [
            'swich_client_id' => [$required, 'string'],
            'swich_client_secret' => [$required, 'string'],
            'swich_ewallet_status' => ['nullable', 'numeric'],
            'swich_biller_status' => ['nullable', 'numeric'],
            'swich_mode' => [$required, 'string'],
            'swich_status' => ['nullable', 'numeric'],
        ];
    }
}
