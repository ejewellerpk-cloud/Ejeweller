<?php

namespace Database\Seeders;

use App\Enums\SwitchBox;
use Illuminate\Database\Seeder;
use App\Models\NotificationAlert;

class NotificationAlertTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public array $notificationAlerts = [
        'name'    => [
            'Order Pending Message',
            'Order Confirmation Message',
            'Order On The Way Message',
            'Order Delivered Message',
            'Order Canceled Message',
            'Order Rejected Message',
            'Admin And Manager New Order Message',
        ],
        'message' => [
            'Thank you for your purchase. Your order has been received and is currently being reviewed by our team.',
            'Good news — your order has been confirmed and is now being prepared for dispatch.',
            'Your order is on its way. We will notify you once it has been delivered.',
            'Your order has been delivered successfully. We hope you enjoy your purchase.',
            'Your order has been canceled as requested. If you need further assistance, please contact our support team.',
            'We are unable to process this order at the moment. Please contact support if you would like more information.',
            'A customer has placed a new order on the store. Please review and process it from the admin panel.',
        ]

    ];

    public function run()
    {
        foreach ($this->notificationAlerts['name'] as $key => $notificationAlert) {
            NotificationAlert::create([
                'name'                      => $notificationAlert,
                'language'                  => str_replace(' ', '_', strtolower($notificationAlert)),
                'mail_message'              => $this->notificationAlerts['message'][$key],
                'sms_message'               => $this->notificationAlerts['message'][$key],
                'push_notification_message' => $this->notificationAlerts['message'][$key],
                'mail'                      => SwitchBox::OFF,
                'sms'                       => SwitchBox::OFF,
                'push_notification'         => SwitchBox::OFF,
            ]);
        }
    }
}
