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
        Schema::create('swich_payin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway_slug', 40);
            $table->string('customer_transaction_id', 50)->unique();
            $table->string('swich_order_id')->nullable();
            $table->string('swich_transaction_id')->nullable();
            $table->string('consumer_number')->nullable();
            $table->string('msisdn', 20)->nullable();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('status', 40)->default('pending');
            $table->unsignedInteger('channel_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'gateway_slug']);
        });

        $misc = json_encode([
            'input' => ['swich.msisdnInput.blade.php'],
            'js' => [],
            'onClick' => false,
            'submit' => false,
        ]);

        $this->refreshGateway('easypaisa', 'EasyPaisa', $misc, [
            ['easypaisa_client_id', InputType::TEXT, ''],
            ['easypaisa_client_secret', InputType::TEXT, ''],
            ['easypaisa_mode', InputType::SELECT, json_encode([GatewayMode::SANDBOX => 'sandbox', GatewayMode::LIVE => 'live'])],
            ['easypaisa_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
        ], ['easypaisa_store_id', 'easypaisa_hash_key', 'easypaisa_username', 'easypaisa_password', 'easypaisa_checksum_secret', 'easypaisa_category_id', 'easypaisa_remote_ip']);

        $this->refreshGateway('jazzcash', 'JazzCash', $misc, [
            ['jazzcash_client_id', InputType::TEXT, ''],
            ['jazzcash_client_secret', InputType::TEXT, ''],
            ['jazzcash_mode', InputType::SELECT, json_encode([GatewayMode::SANDBOX => 'sandbox', GatewayMode::LIVE => 'live'])],
            ['jazzcash_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
        ], ['jazzcash_checksum_secret', 'jazzcash_category_id', 'jazzcash_remote_ip']);

        $this->refreshGateway('biller', '1Bill / Biller', $misc, [
            ['biller_client_id', InputType::TEXT, ''],
            ['biller_client_secret', InputType::TEXT, ''],
            ['biller_mode', InputType::SELECT, json_encode([GatewayMode::SANDBOX => 'sandbox', GatewayMode::LIVE => 'live'])],
            ['biller_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
        ], ['biller_checksum_secret', 'biller_category_id', 'biller_remote_ip']);
    }

    public function down(): void
    {
        Schema::dropIfExists('swich_payin_transactions');
        PaymentGateway::whereIn('slug', ['jazzcash', 'biller'])->delete();
    }

    private function refreshGateway(string $slug, string $name, string $misc, array $options, array $remove = []): void
    {
        $gateway = PaymentGateway::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'misc' => $misc, 'status' => Activity::DISABLE]
        );
        $gateway->name = $name;
        $gateway->misc = $misc;
        $gateway->save();

        if ($remove) {
            GatewayOption::where('model_type', PaymentGateway::class)
                ->where('model_id', $gateway->id)
                ->whereIn('option', $remove)
                ->delete();
        }

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
};
