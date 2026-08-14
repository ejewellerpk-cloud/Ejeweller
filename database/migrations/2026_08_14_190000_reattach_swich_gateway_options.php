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
        $swich = PaymentGateway::where('slug', 'swich')->first();
        if (!$swich) {
            return;
        }

        $morphType = $swich->getMorphClass();

        GatewayOption::where('option', 'like', 'swich_%')->update([
            'model_id' => $swich->id,
            'model_type' => $morphType,
        ]);

        $grouped = GatewayOption::where('model_id', $swich->id)
            ->where('option', 'like', 'swich_%')
            ->get()
            ->groupBy('option');

        foreach ($grouped as $optionName => $rows) {
            if ($rows->count() < 2) {
                continue;
            }

            $keeper = $rows->sortByDesc(function ($row) {
                return (filled($row->value) ? '1' : '0') . str_pad((string) $row->id, 12, '0', STR_PAD_LEFT);
            })->first();

            GatewayOption::where('model_id', $swich->id)
                ->where('option', $optionName)
                ->where('id', '!=', $keeper->id)
                ->delete();
        }

        $selectActivities = json_encode([
            Activity::ENABLE => 'enable',
            Activity::DISABLE => 'disable',
        ]);
        $modeActivities = json_encode([
            GatewayMode::SANDBOX => 'sandbox',
            GatewayMode::LIVE => 'live',
        ]);

        $defaults = [
            'swich_client_id' => [InputType::TEXT, ''],
            'swich_client_secret' => [InputType::TEXT, ''],
            'swich_ewallet_status' => [InputType::SELECT, $selectActivities],
            'swich_biller_status' => [InputType::SELECT, $selectActivities],
            'swich_mode' => [InputType::SELECT, $modeActivities],
            'swich_status' => [InputType::SELECT, $selectActivities],
        ];

        foreach ($defaults as $key => [$type, $activities]) {
            $row = GatewayOption::where('model_id', $swich->id)->where('option', $key)->first();
            if ($row) {
                $row->model_type = $morphType;
                $row->type = $type;
                if ($type === InputType::SELECT && blank($row->activities)) {
                    $row->activities = $activities;
                }
                $row->save();
                continue;
            }

            GatewayOption::create([
                'model_id' => $swich->id,
                'model_type' => $morphType,
                'option' => $key,
                'value' => $type === InputType::SELECT ? (string) Activity::DISABLE : '',
                'type' => $type,
                'activities' => $activities,
            ]);
        }
    }

    public function down(): void
    {
    }
};
