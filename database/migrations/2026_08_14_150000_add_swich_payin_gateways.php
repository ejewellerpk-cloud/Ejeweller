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
        if (!Schema::hasTable('swich_payin_transactions')) {
            Schema::create('swich_payin_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('gateway_slug', 40);
                $table->string('method', 20)->nullable();
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
        }

        $misc = json_encode([
            'input' => ['swich.msisdnInput.blade.php'],
            'js' => [],
            'onClick' => false,
            'submit' => false,
        ]);

        $this->refreshGateway('swich', 'Swich', $misc, [
            ['swich_client_id', InputType::TEXT, ''],
            ['swich_client_secret', InputType::TEXT, ''],
            ['swich_secret_key', InputType::TEXT, ''],
            ['swich_whitelisted_ip', InputType::TEXT, ''],
            ['swich_ewallet_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
            ['swich_biller_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
            ['swich_mode', InputType::SELECT, json_encode([GatewayMode::SANDBOX => 'sandbox', GatewayMode::LIVE => 'live'])],
            ['swich_status', InputType::SELECT, json_encode([Activity::ENABLE => 'enable', Activity::DISABLE => 'disable']), (string) Activity::DISABLE],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('swich_payin_transactions');
        PaymentGateway::where('slug', 'swich')->delete();
    }

    private function refreshGateway(string $slug, string $name, string $misc, array $options): void
    {
        $gateway = PaymentGateway::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'misc' => $misc, 'status' => Activity::DISABLE]
        );
        $gateway->name = $name;
        $gateway->misc = $misc;
        $gateway->save();

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
