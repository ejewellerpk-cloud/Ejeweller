<?php

use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Enums\InputType;
use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('swich_payin_transactions') && !Schema::hasColumn('swich_payin_transactions', 'method')) {
            Schema::table('swich_payin_transactions', function (Blueprint $table) {
                $table->string('method', 20)->nullable()->after('gateway_slug');
            });
        }

        PaymentGateway::whereIn('slug', ['jazzcash', 'biller'])->update(['status' => Activity::DISABLE]);

        $misc = json_encode([
            'input' => ['swich.msisdnInput.blade.php'],
            'js' => [],
            'onClick' => false,
            'submit' => false,
        ]);

        $gateway = PaymentGateway::firstOrCreate(
            ['slug' => 'swich'],
            ['name' => 'Swich', 'misc' => $misc, 'status' => Activity::DISABLE]
        );
        $gateway->name = 'Swich';
        $gateway->misc = $misc;
        $gateway->save();

        $options = [
            ['swich_client_id', InputType::TEXT, ''],
            ['swich_client_secret', InputType::TEXT, ''],
            ['swich_secret_key', InputType::TEXT, ''],
            ['swich_whitelisted_ip', InputType::TEXT, ''],
            ['swich_ewallet_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
            ['swich_biller_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
            ['swich_mode', InputType::SELECT, json_encode([GatewayMode::SANDBOX => 'sandbox', GatewayMode::LIVE => 'live'])],
            ['swich_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
        ];

        foreach ($options as $option) {
            [$key, $type, $activities, $value] = array_pad($option, 4, '');
            GatewayOption::updateOrCreate(
                [
                    'model_id' => $gateway->id,
                    'model_type' => PaymentGateway::class,
                    'option' => $key,
                ],
                [
                    'value' => $value ?? '',
                    'type' => $type,
                    'activities' => $activities,
                ]
            );
        }
    }

    public function down(): void
    {
        PaymentGateway::where('slug', 'swich')->delete();
    }
};
