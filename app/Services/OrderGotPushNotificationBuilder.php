<?php

namespace App\Services;


use App\Enums\Role;
use App\Enums\SwitchBox;
use App\Models\NotificationAlert;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderGotPushNotificationBuilder
{
    public int $orderId;
    public object $order;

    public function __construct($orderId,)
    {
        $this->orderId = $orderId;
        $this->order   = Order::find($orderId);
    }

    public function send(): void
    {
        if (!blank($this->order)) {
            $fcmTokenArray = app(UserFcmTokenService::class)->getActiveTokenStringsForRoles([
                Role::ADMIN,
                Role::MANAGER,
            ]);

            if (count($fcmTokenArray) > 0) {
                try {
                    $notificationAlert = NotificationAlert::where(['language' => 'admin_and_manager_new_order_message'])->first();
                    if ($notificationAlert && $notificationAlert->push_notification == SwitchBox::ON) {
                        $pushNotification = (object)[
                            'title'       => 'New Order Notification',
                            'description' => $notificationAlert->push_notification_message,
                            'order_id'    => $this->orderId
                        ];
                        $firebase         = new FirebaseService();
                        $firebase->sendNotification($pushNotification, $fcmTokenArray, "new-order-found");
                    }
                } catch (Exception $e) {
                    Log::info($e->getMessage());
                }
            }

        }
    }
}
