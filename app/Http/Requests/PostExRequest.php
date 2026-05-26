<?php

namespace App\Http\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostExRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enabled = (int) $this->input('postex_status') === Activity::ENABLE;

        return [
            'postex_status'              => ['required', 'numeric', Rule::in([Activity::ENABLE, Activity::DISABLE])],
            'postex_api_token'           => [$enabled ? 'required' : 'nullable', 'string', 'max:500'],
            'postex_base_url'            => ['nullable', 'string', 'max:500', 'url'],
            'postex_pickup_address_code' => ['nullable', 'string', 'max:50'],
            'postex_default_order_type'  => ['nullable', 'string', Rule::in(['Normal', 'Reverse', 'Replacement', 'Reversed'])],
            'postex_invoice_division'    => ['nullable', 'integer', 'min:1', 'max:99'],
            'postex_booking_weight'      => ['nullable', 'numeric', 'min:0'],
            'postex_auto_ship'           => ['nullable', 'numeric', Rule::in([Activity::ENABLE, Activity::DISABLE])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('postex_default_order_type') && $this->postex_default_order_type === 'Reversed') {
            $this->merge(['postex_default_order_type' => 'Reverse']);
        }
    }
}
