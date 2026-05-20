<?php

namespace Database\Seeders;

use App\Enums\GatewayMode;
use App\Enums\Activity;
use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Dipokhalder\EnvEditor\EnvEditor;
use Illuminate\Database\Seeder;

class PaymentGatewayDataTableSeeder extends Seeder
{
    public array $gateways = [
        [
            "slug" => "paypal",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'paypal_app_id', "value" => env('PAYPAL_APP_ID')],
                ["option" => 'paypal_client_id', "value" => env('PAYPAL_CLIENT_ID')],
                ["option" => 'paypal_client_secret', "value" => env('PAYPAL_CLIENT_SECRET')],
                ["option" => 'paypal_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'paypal_status', "value" => Activity::ENABLE],
            ]
        ],
        [
            "slug" => "stripe",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'stripe_key', "value" => env('STRIPE_KEY')],
                ["option" => 'stripe_secret', "value" => env('STRIPE_SECRET')],
                ["option" => 'stripe_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'stripe_status', "value" => Activity::ENABLE],
            ]
        ],
        [
            "slug" => "flutterwave",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'flutterwave_public_key', "value" => env('FLUTTERWAVE_PUBLIC_KEY')],
                ["option" => 'flutterwave_secret_key', "value" => env('FLUTTERWAVE_SECRET_KEY')],
                ["option" => 'flutterwave_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'flutterwave_status', "value" => Activity::ENABLE],
            ]
        ],
        [
            "slug" => "paystack",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'paystack_public_key', "value" => env('PAYSTACK_PUBLIC_KEY')],
                ["option" => 'paystack_secret_key', "value" => env('PAYSTACK_SECRET_KEY')],
                ["option" => 'paystack_payment_url', "value" => 'https://api.paystack.co'],
                ["option" => 'paystack_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'paystack_status', "value" => Activity::ENABLE],
            ]
        ],
        [
            "slug" => "sslcommerz",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'sslcommerz_store_name', "value" => env('SSLC_STORE_NAME')],
                ["option" => 'sslcommerz_store_id', "value" => env('SSLC_STORE_ID')],
                ["option" => 'sslcommerz_store_password', "value" => env('SSLC_STORE_PASSWORD')],
                ["option" => 'sslcommerz_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'sslcommerz_status', "value" => Activity::ENABLE],
            ]
        ],
        [
            "slug" => "razorpay",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'razorpay_key', "value" => env('RAZORPAY_KEY')],
                ["option" => 'razorpay_secret', "value" => env('RAZORPAY_SECRET')],
                ["option" => 'razorpay_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'razorpay_status', "value" => Activity::ENABLE],
            ]
        ],
        [
            "slug" => "mollie",
            "status" => Activity::ENABLE,
            "options" => [
                ["option" => 'mollie_api_key', "value" => env('MOLLIE_API_KEY')],
                ["option" => 'mollie_mode', "value" => GatewayMode::SANDBOX],
                ["option" => 'mollie_status', "value" => Activity::ENABLE],
            ]
        ],
    ];

    public function run(): void
    {
        $envService = new EnvEditor();
        if ($envService->getValue('DEMO')) {
            foreach ($this->gateways as $gateway) {
                $payment = PaymentGateway::where(['slug' => $gateway['slug']])->first();
                if ($payment) {
                    $payment->status = $gateway['status'];
                    $payment->save();
                }
                $this->gatewayOption($gateway['options']);
            }
        }
    }

    public function gatewayOption($options): void
    {
        if (!blank($options)) {
            foreach ($options as $option) {
                $gatewayOption = GatewayOption::where(['option' => $option['option']])->first();
                if ($gatewayOption) {
                    $gatewayOption->value = $option['value'];
                    $gatewayOption->save();
                }
            }
        }
    }
}
