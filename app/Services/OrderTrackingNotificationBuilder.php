<?php

namespace App\Services;

use App\Enums\SwitchBox;
use App\Mail\OrderMail;
use App\Models\NotificationAlert;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderTrackingNotificationBuilder
{
    public function __construct(public Order $order) {}

    public function send(): void
    {
        $tracking = trim((string) ($this->order->postex_tracking_number ?? ''));
        if ($tracking === '') {
            return;
        }

        $serial = (string) $this->order->order_serial_no;
        $alert  = NotificationAlert::where(['language' => 'order_tracking_message'])->first();
        $fallback = 'Your order #{ORDER_ID} has been booked for delivery. Tracking number: {TRACKING}. Use this number to track your shipment.';

        $this->sendMail($serial, $this->interpolate($alert?->mail_message ?: $fallback, $serial, $tracking));
        $this->sendPush($this->interpolate($alert?->push_notification_message ?: $fallback, $serial, $tracking));
    }

    protected function interpolate(string $template, string $serial, string $tracking): string
    {
        return str_replace(
            ['{ORDER_ID}', '{TRACKING}', '{tracking_number}', '{order_id}'],
            [$serial, $tracking, $tracking, $serial],
            $template
        );
    }

    protected function shouldSendMail(): bool
    {
        $alert = NotificationAlert::where(['language' => 'order_tracking_message'])->first();
        if (!$alert) {
            return true;
        }

        return (int) $alert->mail === SwitchBox::ON;
    }

    protected function shouldSendPush(): bool
    {
        $alert = NotificationAlert::where(['language' => 'order_tracking_message'])->first();
        if (!$alert) {
            return true;
        }

        return (int) $alert->push_notification === SwitchBox::ON;
    }

    protected function resolveRecipient(): array
    {
        $name  = '';
        $email = '';

        $user = User::find($this->order->user_id);
        if ($user) {
            $name  = (string) ($user->name ?? '');
            $email = (string) ($user->email ?? '');
        }

        if ($email === '') {
            $this->order->loadMissing('shippingAddress');
            $address = $this->order->shippingAddress;
            if ($address) {
                if ($name === '') {
                    $name = (string) ($address->full_name ?? '');
                }
                $email = (string) ($address->email ?? '');
            }
        }

        if ($name === '') {
            $name = 'Customer';
        }

        return [$name, $email];
    }

    protected function sendMail(string $serial, string $message): void
    {
        if (!$this->shouldSendMail()) {
            return;
        }

        [$name, $email] = $this->resolveRecipient();
        if ($email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new OrderMail($name, $serial, $message));
        } catch (Exception $e) {
            Log::info('PostEx tracking email failed: ' . $e->getMessage());
        }
    }

    protected function sendPush(string $message): void
    {
        if (!$this->shouldSendPush()) {
            return;
        }

        if (blank($this->order->user_id)) {
            return;
        }

        try {
            $fcmTokenArray = app(UserFcmTokenService::class)->getActiveTokenStrings([$this->order->user_id]);

            if (blank($fcmTokenArray)) {
                $user = User::find($this->order->user_id);
                if (!blank($user)) {
                    if (!blank($user->web_token)) {
                        $fcmTokenArray[] = $user->web_token;
                    }
                    if (!blank($user->device_token)) {
                        $fcmTokenArray[] = $user->device_token;
                    }
                }
            }

            if (blank($fcmTokenArray)) {
                return;
            }

            $pushNotification = (object) [
                'title'       => 'Shipment Tracking',
                'description' => $message,
                'order_id'    => $this->order->id,
            ];

            $firebase = new FirebaseService();
            $firebase->sendNotification($pushNotification, $fcmTokenArray, 'Order Notification');
        } catch (Exception $e) {
            Log::info('PostEx tracking push failed: ' . $e->getMessage());
        }
    }
}
