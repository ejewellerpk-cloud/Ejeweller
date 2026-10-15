<?php

namespace App\Services\PostEx;

use App\Enums\Activity;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Support\Facades\Log;

class PostExStatusSyncService
{
    public function __construct(
        protected PostExSettingsService $settingsService,
        protected PostExApiClient $apiClient,
        protected PostExStatusMapper $mapper,
        protected OrderService $orderService,
    ) {}

    public function isAutoSyncEnabled(): bool
    {
        if (!$this->settingsService->isEnabled()) {
            return false;
        }

        $settings = $this->settingsService->list();

        return (int) ($settings['postex_auto_sync_status'] ?? Activity::ENABLE) === Activity::ENABLE;
    }

    /**
     * Sync PostEx statuses for open booked orders.
     *
     * @return array{checked: int, updated_postex: int, updated_shop: int, errors: int}
     */
    public function sync(int $chunkSize = 25): array
    {
        $stats = [
            'checked'         => 0,
            'updated_postex'  => 0,
            'updated_shop'    => 0,
            'errors'          => 0,
        ];

        if (!$this->isAutoSyncEnabled() || !$this->apiClient->isConfigured()) {
            return $stats;
        }

        $terminal = [OrderStatus::DELIVERED, OrderStatus::CANCELED, OrderStatus::REJECTED];

        Order::query()
            ->whereNotNull('postex_tracking_number')
            ->where('postex_tracking_number', '!=', '')
            ->whereNotIn('status', $terminal)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($orders) use (&$stats) {
                $trackingMap = [];
                foreach ($orders as $order) {
                    $tn = (string) $order->postex_tracking_number;
                    $trackingMap[$tn] = $order;
                }

                if ($trackingMap === []) {
                    return;
                }

                $statusByTracking = $this->fetchStatuses(array_keys($trackingMap));

                foreach ($trackingMap as $tracking => $order) {
                    $stats['checked']++;
                    $postexStatus = $statusByTracking[$tracking] ?? null;
                    if ($postexStatus === null || $postexStatus === '') {
                        continue;
                    }

                    try {
                        $result = $this->applyToOrder($order, $postexStatus);
                        if ($result['postex_changed']) {
                            $stats['updated_postex']++;
                        }
                        if ($result['shop_changed']) {
                            $stats['updated_shop']++;
                        }
                    } catch (Exception $exception) {
                        $stats['errors']++;
                        Log::warning('PostEx status sync failed for order ' . $order->id . ': ' . $exception->getMessage());
                    }
                }
            });

        return $stats;
    }

    /**
     * @return array{postex_changed: bool, shop_changed: bool}
     */
    public function applyToOrder(Order $order, string $postexStatus): array
    {
        $result = ['postex_changed' => false, 'shop_changed' => false];

        $previousPostex = (string) ($order->postex_status ?? '');
        if ($this->mapper->normalize($previousPostex) !== $this->mapper->normalize($postexStatus)) {
            $order->postex_status = $postexStatus;
            $order->save();
            $result['postex_changed'] = true;
            $order = $order->fresh();
        }

        $mapped = $this->mapper->mapToShopStatus($postexStatus);
        if ($mapped === null) {
            return $result;
        }

        if (!$this->mapper->canApply((int) $order->status, $mapped)) {
            return $result;
        }

        $reason = null;
        if (in_array($mapped, [OrderStatus::CANCELED, OrderStatus::REJECTED], true)) {
            $reason = 'Synced from PostEx: ' . $postexStatus;
        }

        $this->orderService->applyExternalStatus($order, $mapped, $reason);
        $result['shop_changed'] = true;

        return $result;
    }

    /**
     * @param  list<string>  $trackingNumbers
     * @return array<string, string> trackingNumber => status
     */
    protected function fetchStatuses(array $trackingNumbers): array
    {
        $out = [];

        try {
            $response = $this->apiClient->trackBulkOrders($trackingNumbers);
            foreach ($this->extractStatusRows($response) as $tracking => $status) {
                if ($tracking !== '' && $status !== '') {
                    $out[$tracking] = $status;
                }
            }
        } catch (Exception $exception) {
            Log::info('PostEx track-bulk failed, falling back to single track: ' . $exception->getMessage());
        }

        foreach ($trackingNumbers as $tracking) {
            if (isset($out[$tracking])) {
                continue;
            }
            try {
                $raw = $this->apiClient->trackOrder($tracking);
                $status = $this->extractSingleStatus($raw);
                if ($status !== null && $status !== '') {
                    $out[$tracking] = $status;
                }
            } catch (Exception $exception) {
                Log::info('PostEx track failed for ' . $tracking . ': ' . $exception->getMessage());
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected function extractStatusRows(array $response): array
    {
        $dist = $response['dist'] ?? null;
        $rows = [];

        if (is_array($dist) && array_is_list($dist)) {
            $rows = $dist;
        } elseif (is_array($dist)) {
            $rows = [$dist];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tracking = (string) ($row['trackingNumber'] ?? $row['TrackingNumber'] ?? '');
            $status = (string) (
                $row['transactionStatus']
                ?? $row['orderStatus']
                ?? $row['shipmentStatus']
                ?? $row['status']
                ?? ''
            );
            if ($tracking !== '' && $status !== '') {
                $out[$tracking] = $status;
            }
        }

        return $out;
    }

    protected function extractSingleStatus(array $raw): ?string
    {
        $dist = $raw['dist'] ?? null;
        if (is_array($dist) && array_is_list($dist)) {
            $dist = $dist[0] ?? [];
        }
        if (!is_array($dist)) {
            return null;
        }

        $status = $dist['transactionStatus']
            ?? $dist['orderStatus']
            ?? $dist['shipmentStatus']
            ?? null;

        return $status !== null && $status !== '' ? (string) $status : null;
    }
}
