<?php

namespace App\Analytics\Http\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;

class IntelligenceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:190'],
            'domain' => ['nullable', 'string', 'max:190'],
            'public_key' => ['nullable', 'string', 'min:20', 'max:64', 'starts_with:pk_', 'regex:/^pk_[A-Za-z0-9]+$/'],
            'analytics_enabled' => ['nullable', 'numeric', 'in:' . Activity::ENABLE . ',' . Activity::DISABLE],
            'allowed_origins' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
