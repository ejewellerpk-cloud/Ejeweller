<?php

use App\Enums\Activity;
use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Dipokhalder\Settings\Facades\Settings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $swich = PaymentGateway::where('slug', 'swich')->first();
        if (!$swich) {
            return;
        }

        $statusOption = GatewayOption::where('model_id', $swich->id)
            ->where('option', 'swich_status')
            ->orderByDesc('id')
            ->first();

        $enabled = (int) ($statusOption?->value ?? $swich->status) === Activity::ENABLE;
        if (!$enabled) {
            return;
        }

        $swich->status = Activity::ENABLE;
        $swich->save();

        $ewallet = GatewayOption::where('model_id', $swich->id)->where('option', 'swich_ewallet_status')->first();
        $biller = GatewayOption::where('model_id', $swich->id)->where('option', 'swich_biller_status')->first();
        $ewalletOn = (int) ($ewallet?->value ?? Activity::DISABLE) === Activity::ENABLE;
        $billerOn = (int) ($biller?->value ?? Activity::DISABLE) === Activity::ENABLE;
        if (!$ewalletOn && !$billerOn && $ewallet) {
            $ewallet->value = (string) Activity::ENABLE;
            $ewallet->save();
        }

        try {
            Settings::group('site')->set([
                'site_online_payment_gateway' => Activity::ENABLE,
            ]);
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
    }
};
