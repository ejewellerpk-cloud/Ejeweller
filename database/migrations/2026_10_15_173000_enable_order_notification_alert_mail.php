<?php

use App\Enums\SwitchBox;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $languages = [
            'order_pending_message',
            'order_confirmation_message',
            'order_on_the_way_message',
            'order_delivered_message',
            'order_canceled_message',
            'order_rejected_message',
            'admin_and_manager_new_order_message',
            'order_tracking_message',
        ];

        DB::table('notification_alerts')
            ->whereIn('language', $languages)
            ->update(['mail' => SwitchBox::ON]);
    }

    public function down(): void
    {
        // Intentionally left blank — do not disable customer mail on rollback.
    }
};
