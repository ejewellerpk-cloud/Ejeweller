<?php

namespace Database\Seeders;

use App\Enums\Activity;
use Dipokhalder\Settings\Facades\Settings;
use Illuminate\Database\Seeder;

class PostExTableSeeder extends Seeder
{
    public function run(): void
    {
        Settings::group('postex')->set([
            'postex_status'              => Activity::DISABLE,
            'postex_api_token'           => env('POSTEX_API_TOKEN', ''),
            'postex_base_url'            => env('POSTEX_BASE_URL', 'https://api.postex.pk/services/integration/api'),
            'postex_pickup_address_code' => '',
            'postex_default_order_type'  => 'Normal',
            'postex_invoice_division'    => 1,
            'postex_booking_weight'      => '',
            'postex_auto_ship'           => Activity::DISABLE,
        ]);
    }
}
