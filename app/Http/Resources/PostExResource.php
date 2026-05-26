<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostExResource extends JsonResource
{
    public $info;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->info = $info;
    }

    public function toArray($request): array
    {
        return [
            'postex_status'              => (int) ($this->info['postex_status'] ?? 10),
            'postex_api_token'           => $this->info['postex_api_token'] ?? '',
            'postex_base_url'            => $this->info['postex_base_url'] ?? config('postex.base_url'),
            'postex_pickup_address_code' => $this->info['postex_pickup_address_code'] ?? '',
            'postex_default_order_type'  => $this->info['postex_default_order_type'] ?? 'Normal',
            'postex_invoice_division'    => (int) ($this->info['postex_invoice_division'] ?? 1),
            'postex_booking_weight'      => $this->info['postex_booking_weight'] ?? '',
            'postex_auto_ship'           => (int) ($this->info['postex_auto_ship'] ?? 10),
        ];
    }
}
