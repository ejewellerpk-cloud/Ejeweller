<?php

namespace App\Services;

use App\Jobs\SendFbCapiEvent;
use Illuminate\Support\Facades\Request;

class FbCapiService
{
    private function hashData($data)
    {
        return $data ? hash('sha256', trim(strtolower($data))) : null;
    }

    public function dispatchEvent($eventName, $userData, $customData = [], $eventId = null, $eventSourceUrl = null)
    {
        $formattedUserData = [
            'client_ip_address' => Request::ip(),
            'client_user_agent' => Request::header('User-Agent'),
        ];

        if (!empty($userData['email']) || !empty($userData['em'])) {
            $formattedUserData['em'] = $this->hashData($userData['email'] ?? $userData['em']);
        }
        if (!empty($userData['phone']) || !empty($userData['ph'])) {
            $phone = $userData['phone'] ?? $userData['ph'];
            $formattedUserData['ph'] = $this->hashData(preg_replace('/[^0-9]/', '', (string) $phone));
        }
        if (!empty($userData['first_name']) || !empty($userData['fn'])) {
            $formattedUserData['fn'] = $this->hashData($userData['first_name'] ?? $userData['fn']);
        }
        if (!empty($userData['last_name']) || !empty($userData['ln'])) {
            $formattedUserData['ln'] = $this->hashData($userData['last_name'] ?? $userData['ln']);
        }
        if (!empty($userData['city'])) {
            $formattedUserData['ct'] = $this->hashData($userData['city']);
        }
        if (!empty($userData['state'])) {
            $formattedUserData['st'] = $this->hashData($userData['state']);
        }
        if (!empty($userData['country'])) {
            $formattedUserData['country'] = $this->hashData($userData['country']);
        }
        if (!empty($userData['external_id']) || !empty($userData['user_id'])) {
            $formattedUserData['external_id'] = $this->hashData((string) ($userData['external_id'] ?? $userData['user_id']));
        }

        $eventData = [
            'event_source_url' => $eventSourceUrl ?: (Request::header('Referer') ?: Request::url()),
        ];

        if ($eventId) {
            $eventData['event_id'] = $eventId;
        }

        SendFbCapiEvent::dispatch($eventName, $eventData, $formattedUserData, $customData);
    }

    public function sendPurchaseEvent($order)
    {
        $userData = [];
        if ($order->user) {
            $userData['email'] = $order->user->email;
            $userData['phone'] = $order->user->phone;
            
            $nameParts = explode(' ', $order->user->name);
            $userData['first_name'] = $nameParts[0];
            if (count($nameParts) > 1) {
                $userData['last_name'] = end($nameParts);
            }
        }

        $contentIds = [];
        if ($order->orderItems) {
            foreach ($order->orderItems as $item) {
                $contentIds[] = $item->product_id;
            }
        }

        $currencyId = \Dipokhalder\Settings\Facades\Settings::group('site')->get('site_default_currency');
        $currency = \App\Models\Currency::find($currencyId);
        $currencyCode = $currency ? $currency->code : 'PKR';

        $customData = [
            'value' => (float) $order->total,
            'currency' => $currencyCode,
            'content_type' => 'product',
            'content_ids' => $contentIds,
            'order_id' => $order->order_serial_no
        ];

        // Deduplication event_id (e.g., order tracking number)
        $eventId = 'purchase_' . $order->order_serial_no;

        $this->dispatchEvent('Purchase', $userData, $customData, $eventId);
    }
}
