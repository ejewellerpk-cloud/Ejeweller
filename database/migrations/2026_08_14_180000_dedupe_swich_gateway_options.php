<?php

use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Enums\InputType;
use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $gateways = PaymentGateway::with('gatewayOptions')->get();

        foreach ($gateways as $gateway) {
            $grouped = $gateway->gatewayOptions->groupBy('option');
            foreach ($grouped as $optionName => $rows) {
                if ($rows->count() < 2) {
                    continue;
                }

                $keeper = $rows->sortByDesc(function ($row) {
                    $filled = filled($row->value) ? 1 : 0;

                    return sprintf('%d-%020d', $filled, (int) $row->id);
                })->first();

                GatewayOption::where('model_type', PaymentGateway::class)
                    ->where('model_id', $gateway->id)
                    ->where('option', $optionName)
                    ->where('id', '!=', $keeper->id)
                    ->delete();
            }
        }

        $swich = PaymentGateway::where('slug', 'swich')->first();
        if (!$swich) {
            return;
        }

        $required = [
            'swich_client_id' => [InputType::TEXT, ''],
            'swich_client_secret' => [InputType::TEXT, ''],
            'swich_ewallet_status' => [InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
            'swich_biller_status' => [InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
            'swich_mode' => [InputType::SELECT, json_encode([GatewayMode::SANDBOX => 'sandbox', GatewayMode::LIVE => 'live'])],
            'swich_status' => [InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
        ];

        foreach ($required as $key => $meta) {
            [$type, $activities, $default] = array_pad($meta, 3, '');
            $existing = GatewayOption::where('model_type', PaymentGateway::class)
                ->where('model_id', $swich->id)
                ->where('option', $key)
                ->first();

            if ($existing) {
                $existing->type = $type;
                $existing->activities = $activities;
                $existing->save();
                continue;
            }

            GatewayOption::create([
                'model_id' => $swich->id,
                'model_type' => PaymentGateway::class,
                'option' => $key,
                'value' => $default ?? '',
                'type' => $type,
                'activities' => $activities,
            ]);
        }
    }

    public function down(): void
    {
    }
};
