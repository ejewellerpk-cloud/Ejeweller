<?php

namespace App\Services\PostEx;

use App\Libraries\AppLibrary;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PostExShipmentService
{
    public function __construct(
        protected PostExApiClient $postExApiClient,
        protected PostExSettingsService $postExSettingsService,
    ) {
    }

    /**
     * Status summary cards for orders booked on PostEx (local DB).
     */
    public function summary(Request $request): array
    {
        $query = $this->baseQuery($request);

        $total = (clone $query)->count();

        $statuses = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(postex_status), ''), 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'key'   => (string) $row->label,
                'label' => (string) $row->label,
                'total' => (int) $row->total,
                'tone'  => $this->statusTone((string) $row->label),
            ])
            ->values()
            ->all();

        return [
            'total'    => $total,
            'statuses' => $statuses,
        ];
    }

    /**
     * Paginated PostEx-booked orders with customer / address details.
     */
    public function list(Request $request): LengthAwarePaginator
    {
        $query = $this->baseQuery($request)
            ->with(['user', 'shippingAddress', 'paymentMethod', 'orderProducts'])
            ->orderByDesc('postex_booked_at')
            ->orderByDesc('id');

        if ($request->filled('postex_status')) {
            $status = trim((string) $request->input('postex_status'));
            if (strcasecmp($status, 'Unknown') === 0) {
                $query->where(function ($q) {
                    $q->whereNull('postex_status')->orWhereRaw("TRIM(postex_status) = ''");
                });
            } else {
                $query->where('postex_status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_serial_no', 'like', '%' . $search . '%')
                    ->orWhere('postex_tracking_number', 'like', '%' . $search . '%')
                    ->orWhereHas('shippingAddress', function ($address) use ($search) {
                        $address->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('city', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->paginate($request->integer('per_page', 20));
    }

    /**
     * Optional live pull from PostEx get-all-order for a status + date range.
     *
     * @return array{orders: array, raw: mixed}
     */
    public function liveList(Request $request): array
    {
        if (!$this->postExSettingsService->isEnabled()) {
            throw new Exception('PostEx integration is disabled or not configured.', 422);
        }

        $request->validate([
            'orderStatusID' => ['required', 'integer', 'min:0'],
            'fromDate'      => ['required', 'date'],
            'toDate'        => ['required', 'date', 'after_or_equal:fromDate'],
        ]);

        $data = $this->postExApiClient->listOrders(
            (int) $request->query('orderStatusID'),
            $request->query('fromDate'),
            $request->query('toDate')
        );

        $rows = $data['dist'] ?? $data;
        if (!is_array($rows)) {
            $rows = [];
        }

        // Normalize list payloads (API sometimes wraps a single object).
        if (isset($rows['trackingNumber']) || isset($rows['TrackingNumber'])) {
            $rows = [$rows];
        }

        $trackingNumbers = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tn = (string) ($row['trackingNumber'] ?? $row['TrackingNumber'] ?? '');
            if ($tn !== '') {
                $trackingNumbers[] = $tn;
            }
        }

        $localByTracking = Order::query()
            ->with(['shippingAddress', 'user'])
            ->whereIn('postex_tracking_number', $trackingNumbers)
            ->get()
            ->keyBy('postex_tracking_number');

        $orders = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tracking = (string) ($row['trackingNumber'] ?? $row['TrackingNumber'] ?? '');
            $local = $tracking !== '' ? $localByTracking->get($tracking) : null;

            $orders[] = [
                'tracking_number'   => $tracking,
                'order_ref'         => (string) ($row['orderRefNumber'] ?? $row['OrderRefNumber'] ?? $row['merchantOrderId'] ?? ''),
                'customer_name'     => (string) ($row['customerName'] ?? $row['CustomerName'] ?? optional($local?->shippingAddress)->full_name ?? ''),
                'customer_phone'    => (string) ($row['customerPhone'] ?? $row['CustomerPhone'] ?? optional($local?->shippingAddress)->phone ?? ''),
                'customer_city'     => (string) ($row['customerCity'] ?? $row['CustomerCity'] ?? optional($local?->shippingAddress)->city ?? ''),
                'customer_address'  => (string) ($row['customerAddress'] ?? $row['CustomerAddress'] ?? optional($local?->shippingAddress)->address ?? ''),
                'cod_amount'        => $row['orderCash'] ?? $row['OrderCash'] ?? $row['codAmount'] ?? null,
                'status'            => (string) ($row['transactionStatus'] ?? $row['orderStatus'] ?? $row['Status'] ?? ''),
                'status_id'         => $row['transactionStatusId'] ?? $row['orderStatusId'] ?? $row['statusId'] ?? null,
                'booking_date'      => (string) ($row['orderDate'] ?? $row['OrderDate'] ?? $row['createdAt'] ?? ''),
                'items_count'       => $row['itemsCount'] ?? $row['ItemsCount'] ?? null,
                'local_order_id'    => $local?->id,
                'order_serial_no'   => $local?->order_serial_no,
                'raw'               => $row,
            ];
        }

        return [
            'orders' => $orders,
            'count'  => count($orders),
        ];
    }

    public function liveStatuses(): array
    {
        if (!$this->postExSettingsService->isEnabled()) {
            throw new Exception('PostEx integration is disabled or not configured.', 422);
        }

        $data = $this->postExApiClient->getOrderStatuses();
        $rows = $data['dist'] ?? $data;
        if (!is_array($rows)) {
            return [];
        }

        if (isset($rows['statusId']) || isset($rows['StatusId'])) {
            $rows = [$rows];
        }

        $statuses = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['statusId'] ?? $row['StatusId'] ?? $row['id'] ?? 0);
            $name = (string) ($row['statusName'] ?? $row['StatusName'] ?? $row['name'] ?? '');
            if ($id <= 0 && $name === '') {
                continue;
            }
            $statuses[] = [
                'id'    => $id,
                'label' => $name !== '' ? $name : ('Status #' . $id),
                'tone'  => $this->statusTone($name),
            ];
        }

        return $statuses;
    }

    protected function baseQuery(Request $request)
    {
        $query = Order::query()
            ->whereNotNull('postex_tracking_number')
            ->where('postex_tracking_number', '!=', '');

        if ($request->filled('from_date')) {
            $query->whereDate('postex_booked_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('postex_booked_at', '<=', $request->input('to_date'));
        }

        return $query;
    }

    public function transformOrder(Order $order): array
    {
        $shipping = $order->shippingAddress;

        return [
            'id'                     => $order->id,
            'order_serial_no'        => $order->order_serial_no,
            'postex_tracking_number' => $order->postex_tracking_number,
            'postex_status'          => $order->postex_status ?: 'Unknown',
            'postex_status_tone'     => $this->statusTone((string) ($order->postex_status ?: 'Unknown')),
            'postex_booked_at'       => $order->postex_booked_at ? AppLibrary::datetime($order->postex_booked_at) : null,
            'order_datetime'         => $order->order_datetime ? AppLibrary::datetime($order->order_datetime) : null,
            'status'                 => $order->status,
            'status_name'            => trans('orderStatus.' . $order->status),
            'payment_status'         => $order->payment_status,
            'payment_method_name'    => $order->paymentMethod?->name,
            'total'                  => (float) $order->total,
            'total_currency_price'   => AppLibrary::currencyAmountFormat($order->total),
            'items_count'            => $order->orderProducts?->count() ?? 0,
            'customer'               => [
                'name'    => $shipping?->full_name ?: ($order->user?->name ?? ''),
                'phone'   => $shipping?->phone ?: ($order->user?->phone ?? ''),
                'email'   => $shipping?->email ?: ($order->user?->email ?? ''),
                'address' => $shipping?->address ?? '',
                'city'    => $shipping?->city ?? '',
                'state'   => $shipping?->state ?? '',
                'zip_code'=> $shipping?->zip_code ?? '',
            ],
            'note'                   => $order->note,
        ];
    }

    public function statusTone(string $status): string
    {
        $key = strtolower(trim($status));
        $key = str_replace(['_', '-'], ' ', $key);

        if ($key === '' || $key === 'unknown') {
            return 'slate';
        }
        if (str_contains($key, 'deliver') && !str_contains($key, 'out for')) {
            return 'emerald';
        }
        if (str_contains($key, 'return')) {
            return 'orange';
        }
        if (str_contains($key, 'cancel') || str_contains($key, 'expir') || str_contains($key, 'un assigned') || str_contains($key, 'unassigned')) {
            return 'rose';
        }
        if (str_contains($key, 'out for delivery') || str_contains($key, 'attempt')) {
            return 'amber';
        }
        if (str_contains($key, 'warehouse') || str_contains($key, 'picked') || str_contains($key, 'en route') || str_contains($key, 'en-route')) {
            return 'indigo';
        }
        if (str_contains($key, 'booked') && !str_contains($key, 'unbooked')) {
            return 'sky';
        }

        return 'slate';
    }
}
