<?php

namespace App\Http\Requests;

use App\Rules\ValidJsonOrder;
use Illuminate\Foundation\Http\FormRequest;

class WhatsappOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'        => ['required', 'numeric'],
            'subtotal'           => ['required', 'numeric'],
            'discount'           => ['nullable', 'numeric'],
            'tax'                => ['required', 'numeric'],
            'shipping_charge'    => ['nullable', 'numeric', 'min:0'],
            'total'              => ['required', 'numeric'],
            'payment_method'     => ['required', 'numeric'],
            'products'           => ['required', 'json', new ValidJsonOrder],
            'note'               => ['nullable', 'string', 'max:2000'],
            'shipping_full_name' => ['required', 'string', 'max:191'],
            'shipping_phone'     => ['required', 'string', 'max:50'],
            'shipping_email'     => ['nullable', 'email', 'max:191'],
            'shipping_country_code' => ['nullable', 'string', 'max:20'],
            'shipping_address'   => ['required', 'string', 'max:500'],
            'shipping_city'      => ['nullable', 'string', 'max:191'],
            'shipping_state'     => ['nullable', 'string', 'max:191'],
            'shipping_country'   => ['nullable', 'string', 'max:191'],
            'shipping_zip_code'  => ['nullable', 'string', 'max:50'],
        ];
    }
}
