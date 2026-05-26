<?php

namespace App\Services\PostEx;

use App\Enums\OrderType;
use App\Exceptions\PostExApiException;
use App\Models\Order;
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

        return [
            'order'    => $order->fresh(['shippingAddress', 'orderProducts.product']),
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
