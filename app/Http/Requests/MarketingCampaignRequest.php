<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarketingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('campaign')?->id ?? $this->route('campaign');

        return [
            'name' => ['required', 'string', 'max:190'],
            'channel' => ['nullable', 'string', 'max:32'],
            'objective' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'max:16'],
            'utm_source' => ['nullable', 'string', 'max:64'],
            'utm_medium' => ['nullable', 'string', 'max:64'],
            'utm_campaign' => [
                'nullable',
                'string',
                'max:128',
                Rule::unique('marketing_campaigns', 'utm_campaign')->ignore($id),
            ],
            'utm_content' => ['nullable', 'string', 'max:128'],
            'utm_term' => ['nullable', 'string', 'max:128'],
            'landing_path' => ['nullable', 'string', 'max:255'],
            'daily_budget' => ['nullable', 'numeric', 'min:0'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'external_campaign_id' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
