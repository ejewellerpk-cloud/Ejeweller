<?php

namespace App\Services\PostEx;

use App\Enums\OrderType;
use App\Exceptions\PostExApiException;
use App\Models\Order;
use App\Services\OrderTrackingNotificationBuilder;
use Exception;
use Illuminate\Support\Facades\Log;

class PostExOrderService
{
    public function __construct(
        protected PostExSettingsService $settingsService,
        protected PostExApiClient $apiClient
    ) {}

    /**
     * @throws Exception
     */
    public function createShipment(Order $order): array
    {
        $this->assertEnabled();

        if (!empty($order->postex_tracking_number)) {
            throw new Exception('This order is already booked with PostEx.', 422);
        }

        $order->loadMissing(['shippingAddress', 'orderProducts.product']);

        if ($order->order_type !== OrderType::DELIVERY) {
            throw new Exception('PostEx shipment is only available for delivery orders.', 422);
        }

        $address = $order->shippingAddress;
        if (!$address) {
            throw new Exception('Shipping address is required to book with PostEx.', 422);
        }

        $settings = $this->settingsService->list();
        $payload  = $this->buildCreateOrderPayload($order, $address, $settings);

        try {
            $response = $this->apiClient->createOrder($payload);
        } catch (PostExApiException $exception) {
            throw new Exception($exception->getMessage(), 422);
        }

        $dist = $response['dist'] ?? [];
        $order->update([
            'postex_tracking_number' => $dist['trackingNumber'] ?? null,
            'postex_status'          => $dist['orderStatus'] ?? null,
            'postex_booked_at'       => now(),
        ]);

        $freshOrder = $order->fresh(['shippingAddress', 'orderProducts.product']);

        try {
            if (!empty($freshOrder->postex_tracking_number)) {
                (new OrderTrackingNotificationBuilder($freshOrder))->send();
            }
        } catch (Exception $exception) {
            Log::info('PostEx tracking notification failed: ' . $exception->getMessage());
        }

        return [
            'order'    => $freshOrder,
            'postex'   => $response,
        ];
    }

    /**
     * @throws Exception
     */
    public function track(Order $order): array
    {
        $this->assertEnabled();
        $tracking = $this->trackingNumber($order);

        try {
            return $this->apiClient->trackOrder($tracking);
        } catch (PostExApiException $exception) {
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * Safe customer-facing PostEx tracking (null if not booked / PostEx off / API error).
     */
    public function publicTrackingForOrder(Order $order): ?array
    {
        if (empty($order->postex_tracking_number)) {
            return null;
        }

        if (!$this->settingsService->isEnabled() || !$this->apiClient->isConfigured()) {
            return [
                'tracking_number' => (string) $order->postex_tracking_number,
                'status'          => $order->postex_status,
                'history'         => [],
                'available'       => false,
                'message'         => 'Courier live tracking is temporarily unavailable.',
            ];
        }

        try {
            $raw = $this->apiClient->trackOrder((string) $order->postex_tracking_number);

            return $this->normalizePublicTracking($raw, (string) $order->postex_tracking_number, $order->postex_status);
        } catch (Exception $exception) {
            Log::info('PostEx public track failed: ' . $exception->getMessage());

            return [
                'tracking_number' => (string) $order->postex_tracking_number,
                'status'          => $order->postex_status,
                'history'         => [],
                'available'       => false,
                'message'         => 'Courier live tracking is temporarily unavailable.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{tracking_number: string, status: ?string, history: array<int, array{status: string, date: ?string, code: ?string}>, available: bool, message: ?string}
     */
    protected function normalizePublicTracking(array $raw, string $fallbackTracking, ?string $fallbackStatus): array
    {
        $dist = $raw['dist'] ?? null;
        if (is_array($dist) && array_is_list($dist)) {
            $dist = $dist[0] ?? [];
        }
        if (!is_array($dist)) {
            $dist = [];
        }

        $status = $dist['transactionStatus']
            ?? $dist['orderStatus']
            ?? $dist['shipmentStatus']
            ?? $fallbackStatus;

        $tracking = $dist['trackingNumber'] ?? $fallbackTracking;

        $historyRaw = $dist['transactionStatusHistory']
            ?? $dist['trackingHistory']
            ?? $dist['history']
            ?? [];

        if (!is_array($historyRaw)) {
            $historyRaw = [];
        }

        $history = [];
        foreach ($historyRaw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $message = $row['transactionStatusMessage']
                ?? $row['statusMessage']
                ?? $row['message']
                ?? $row['status']
                ?? null;
            if ($message === null || $message === '') {
                continue;
            }
            $history[] = [
                'status' => (string) $message,
                'date'   => isset($row['transactionStatusDate'])
                    ? (string) $row['transactionStatusDate']
                    : (isset($row['date']) ? (string) $row['date'] : null),
                'code'   => isset($row['transactionStatusMessageCode'])
                    ? (string) $row['transactionStatusMessageCode']
                    : (isset($row['code']) ? (string) $row['code'] : null),
            ];
        }

        return [
            'tracking_number' => (string) $tracking,
            'status'          => $status !== null && $status !== '' ? (string) $status : null,
            'history'         => $history,
            'available'       => true,
            'message'         => null,
        ];
    }

    /**
     * @throws Exception
     */
    public function cancel(Order $order): array
    {
        $this->assertEnabled();
        $tracking = $this->trackingNumber($order);

        try {
            $response = $this->apiClient->cancelOrder($tracking);
        } catch (PostExApiException $exception) {
            throw new Exception($exception->getMessage(), 422);
        }

        $order->update([
            'postex_status' => 'Cancelled',
        ]);

        return $response;
    }

    /**
     * @throws Exception
     */
    public function paymentStatus(Order $order): array
    {
        $this->assertEnabled();
        $tracking = $this->trackingNumber($order);

        try {
            return $this->apiClient->paymentStatus($tracking);
        } catch (PostExApiException $exception) {
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function airwayBill(Order $order): \Illuminate\Http\Client\Response
    {
        $this->assertEnabled();
        $tracking = $this->trackingNumber($order);

        return $this->apiClient->getAirwayBill([$tracking]);
    }

    protected function buildCreateOrderPayload(Order $order, $address, array $settings): array
    {
        $items = max(1, (int) $order->orderProducts->sum(fn ($line) => abs((int) $line->quantity)));

        $orderType = (string) ($settings['postex_default_order_type'] ?? 'Normal');
        if ($orderType === 'Reversed') {
            $orderType = 'Reverse';
        }

        $payload = [
            'orderRefNumber'  => (string) $order->order_serial_no,
            'invoicePayment'  => (string) $order->total,
            'orderDetail'     => $this->buildOrderDetail($order),
            'customerName'    => (string) $address->full_name,
            'customerPhone'   => $this->normalizePhone((string) $address->phone, (string) $address->country_code),
            'deliveryAddress' => trim(implode(', ', array_filter([
                $address->address,
                $address->state,
                $address->zip_code,
            ]))),
            'cityName'        => (string) $address->city,
            'invoiceDivision' => (int) ($settings['postex_invoice_division'] ?? 1),
            'items'           => $items,
            'orderType'       => $orderType,
        ];

        if (!empty($settings['postex_pickup_address_code'])) {
            $payload['pickupAddressCode'] = (string) $settings['postex_pickup_address_code'];
        }

        if (!empty($order->note)) {
            $payload['transactionNotes'] = (string) $order->note;
        }

        if (!empty($settings['postex_booking_weight'])) {
            $payload['bookingWeight'] = (float) $settings['postex_booking_weight'];
        }

        return $payload;
    }

    protected function buildOrderDetail(Order $order): string
    {
        $parts = $order->orderProducts->map(function ($line) {
            $name = $line->product?->name ?? 'Item';
            $qty  = abs((int) $line->quantity);

            return $qty . ' x ' . $name;
        })->filter()->values()->all();

        $detail = implode(', ', $parts);

        return $detail !== '' ? $detail : 'Order #' . $order->order_serial_no;
    }

    protected function normalizePhone(string $phone, string $countryCode = ''): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '92') && strlen($digits) >= 12) {
            $digits = '0' . substr($digits, 2);
        }

        if ($digits !== '' && $digits[0] !== '0' && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        return $digits !== '' ? $digits : $phone;
    }

    /**
     * @throws Exception
     */
    protected function trackingNumber(Order $order): string
    {
        if (empty($order->postex_tracking_number)) {
            throw new Exception('No PostEx tracking number found for this order.', 422);
        }

        return (string) $order->postex_tracking_number;
    }

    /**
     * @throws Exception
     */
    protected function assertEnabled(): void
    {
        if (!$this->settingsService->isEnabled()) {
            throw new Exception('PostEx integration is disabled or not configured.', 422);
        }

        if (!$this->apiClient->isConfigured()) {
            throw new Exception('PostEx API token is missing.', 422);
        }
    }
}
