<?php

use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $extraGateways = PaymentGateway::whereIn('slug', ['jazzcash', 'biller'])->get();
        foreach ($extraGateways as $gateway) {
            GatewayOption::where('model_type', PaymentGateway::class)
                ->where('model_id', $gateway->id)
                ->delete();
            $gateway->delete();
        }

        GatewayOption::where('model_type', PaymentGateway::class)
            ->whereIn('option', ['swich_secret_key', 'swich_whitelisted_ip'])
            ->delete();
    }

    public function down(): void
    {
    }
};
