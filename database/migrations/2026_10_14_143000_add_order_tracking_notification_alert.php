<?php

use App\Enums\SwitchBox;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $language = 'order_tracking_message';

    private string $message = 'Your order #{ORDER_ID} has been booked for delivery. Tracking number: {TRACKING}. Use this number to track your shipment.';

    public function up(): void
    {
        $exists = DB::table('notification_alerts')
            ->where('language', $this->language)
            ->exists();

        if ($exists) {
            DB::table('notification_alerts')
                ->where('language', $this->language)
                ->update([
                    'mail' => SwitchBox::ON,
                    'push_notification' => SwitchBox::ON,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('notification_alerts')->insert([
            'name' => 'Order Tracking Message',
            'language' => $this->language,
            'mail_message' => $this->message,
            'sms_message' => $this->message,
            'push_notification_message' => $this->message,
            'mail' => SwitchBox::ON,
            'sms' => SwitchBox::OFF,
            'push_notification' => SwitchBox::ON,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('notification_alerts')
            ->where('language', $this->language)
            ->delete();
    }
};
