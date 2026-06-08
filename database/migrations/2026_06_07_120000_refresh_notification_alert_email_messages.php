<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $replacements = [
        'order_pending_message' => [
            'old' => 'Your order is successfully placed.',
            'new' => 'Thank you for your purchase. Your order has been received and is currently being reviewed by our team.',
        ],
        'order_confirmation_message' => [
            'old' => 'Your order is confirmed.',
            'new' => 'Good news — your order has been confirmed and is now being prepared for dispatch.',
        ],
        'order_on_the_way_message' => [
            'old' => 'Your order is on the way.',
            'new' => 'Your order is on its way. We will notify you once it has been delivered.',
        ],
        'order_delivered_message' => [
            'old' => 'Your order is successfully delivered.',
            'new' => 'Your order has been delivered successfully. We hope you enjoy your purchase.',
        ],
        'order_canceled_message' => [
            'old' => 'Your order is canceled.',
            'new' => 'Your order has been canceled as requested. If you need further assistance, please contact our support team.',
        ],
        'order_rejected_message' => [
            'old' => 'Your order is rejected.',
            'new' => 'We are unable to process this order at the moment. Please contact support if you would like more information.',
        ],
        'admin_and_manager_new_order_message' => [
            'old' => 'You have a new order.',
            'new' => 'A customer has placed a new order on the store. Please review and process it from the admin panel.',
        ],
    ];

    public function up(): void
    {
        foreach ($this->replacements as $language => $messages) {
            DB::table('notification_alerts')
                ->where('language', $language)
                ->where('mail_message', $messages['old'])
                ->update([
                    'mail_message' => $messages['new'],
                    'sms_message' => $messages['new'],
                    'push_notification_message' => $messages['new'],
                ]);
        }
    }

    public function down(): void
    {
        foreach ($this->replacements as $language => $messages) {
            DB::table('notification_alerts')
                ->where('language', $language)
                ->where('mail_message', $messages['new'])
                ->update([
                    'mail_message' => $messages['old'],
                    'sms_message' => $messages['old'],
                    'push_notification_message' => $messages['old'],
                ]);
        }
    }
};
