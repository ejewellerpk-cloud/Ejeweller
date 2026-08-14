<?php

namespace App\Http\PaymentGateways\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class Biller extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = request()->biller_status == Activity::ENABLE ? 'required' : 'nullable';

        return [
            'biller_client_id' => [$required, 'string'],
            'biller_client_secret' => [$required, 'string'],
            'biller_mode' => [$required, 'string'],
            'biller_status' => ['nullable', 'numeric'],
        ];
    }
}
