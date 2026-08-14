<?php

namespace App\Http\PaymentGateways\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class Jazzcash extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = request()->jazzcash_status == Activity::ENABLE ? 'required' : 'nullable';

        return [
            'jazzcash_client_id' => [$required, 'string'],
            'jazzcash_client_secret' => [$required, 'string'],
            'jazzcash_mode' => [$required, 'string'],
            'jazzcash_status' => ['nullable', 'numeric'],
        ];
    }
}
