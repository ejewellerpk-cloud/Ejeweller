<?php

namespace App\Services;


use App\Enums\OrderStatus;
use App\Enums\SwitchBox;
use App\Mail\OrderMail;
use App\Models\NotificationAlert;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderMailNotificationBuilder
{
    public int $orderId;
    public mixed $status;
    public object $order;

    public function __construct($orderId, $status)
    {
        $this->orderId = $orderId;
        $this->status  = $status;
        $this->order   = Order::with(['shippingAddress', 'user'])->find($orderId);
    }

    public function send(): void
    {
        if (blank($this->order)) {
            return;
        }

        $recipient = $this->resolveRecipient();
        if (!$recipient) {
            Log::info('Order mail skipped: no recipient email', [
                'order_id' => $this->orderId,
                'user_id'  => $this->order->user_id,
                'status'   => $this->status,
            ]);

            return;
        }

        $this->message($recipient['name'], $recipient['email'], $this->status, $this->order->order_serial_no);
    }

    /**
     * Prefer users.email, then shipping/billing address email on the order.
     */
    private function resolveRecipient(): ?array
    {
        $user = $this->order->user ?: User::find($this->order->user_id);
        $name = $user?->name
            ?: (string) ($this->order->shippingAddress?->full_name ?? 'Customer');

        $billingEmail = $this->order->address()
            ->where('address_type', \App\Enums\AddressType::BILLING)
            ->value('email');

        $candidates = [
            $user?->email,
            $this->order->shippingAddress?->email,
            $billingEmail,
        ];

        foreach ($candidates as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ($user && blank($user->email)) {
                    try {
                        $user->email = $email;
                        $user->save();
                    } catch (Exception $e) {
                        Log::info('Could not backfill user email for order mail: ' . $e->getMessage());
                    }
                }

                return ['name' => $name, 'email' => $email];
            }
        }

        return null;
    }

    private function message($name, $email, $status, $orderId): void
    {
        if ($status == OrderStatus::PENDING) {
            $this->pending($name, $email, $orderId);
        } elseif ($status == OrderStatus::CONFIRMED) {
            $this->confirmed($name, $email, $orderId);
        } elseif ($status == OrderStatus::ON_THE_WAY) {
            $this->onTheWay($name, $email, $orderId);
        } elseif ($status == OrderStatus::DELIVERED) {
            $this->delivered($name, $email, $orderId);
        } elseif ($status == OrderStatus::CANCELED) {
            $this->canceled($name, $email, $orderId);
        } elseif ($status == OrderStatus::REJECTED) {
            $this->rejected($name, $email, $orderId);
        }
    }

    private function mail($name, $email, $orderId, $message): void
    {
        try {
            Mail::to($email)->send(new OrderMail($name, $orderId, $message));
        } catch (Exception $e) {
            Log::info('Order mail send failed: ' . $e->getMessage(), [
                'order_id' => $this->orderId,
                'email'    => $email,
            ]);
        }
    }

    private function pending($name, $email, $orderId): void
    {
        $notificationAlert = NotificationAlert::where(['language' => 'order_pending_message'])->first();
        if ($notificationAlert && $notificationAlert->mail == SwitchBox::ON) {
            $this->mail($name, $email, $orderId, $notificationAlert->mail_message);
        } else {
            Log::info('Order pending mail skipped: alert off or missing', ['order_id' => $this->orderId]);
        }
    }

    private function confirmed($name, $email, $orderId): void
    {
        $notificationAlert = NotificationAlert::where(['language' => 'order_confirmation_message'])->first();
        if ($notificationAlert && $notificationAlert->mail == SwitchBox::ON) {
            $this->mail($name, $email, $orderId, $notificationAlert->mail_message);
        }
    }

    private function onTheWay($name, $email, $orderId): void
    {
        $notificationAlert = NotificationAlert::where(['language' => 'order_on_the_way_message'])->first();
        if ($notificationAlert && $notificationAlert->mail == SwitchBox::ON) {
            $this->mail($name, $email, $orderId, $notificationAlert->mail_message);
        }
    }

    private function delivered($name, $email, $orderId): void
    {
        $notificationAlert = NotificationAlert::where(['language' => 'order_delivered_message'])->first();
        if ($notificationAlert && $notificationAlert->mail == SwitchBox::ON) {
            $this->mail($name, $email, $orderId, $notificationAlert->mail_message);
        }
    }

    private function canceled($name, $email, $orderId): void
    {
        $notificationAlert = NotificationAlert::where(['language' => 'order_canceled_message'])->first();
        if ($notificationAlert && $notificationAlert->mail == SwitchBox::ON) {
            $this->mail($name, $email, $orderId, $notificationAlert->mail_message);
        }
    }

    private function rejected($name, $email, $orderId): void
    {
        $notificationAlert = NotificationAlert::where(['language' => 'order_rejected_message'])->first();
        if ($notificationAlert && $notificationAlert->mail == SwitchBox::ON) {
            $this->mail($name, $email, $orderId, $notificationAlert->mail_message);
        }
    }
}
