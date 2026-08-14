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
        $status = request()->input('swich_status');
        $enabled = in_array((string) $status, [(string) Activity::ENABLE, '5'], true);
        $required = $enabled ? 'required' : 'nullable';

        return [
            'swich_client_id' => [$required],
            'swich_client_secret' => [$required],
            'swich_ewallet_status' => ['nullable'],
            'swich_biller_status' => ['nullable'],
            'swich_mode' => [$required],
            'swich_status' => ['required'],
        ];
    }
}
