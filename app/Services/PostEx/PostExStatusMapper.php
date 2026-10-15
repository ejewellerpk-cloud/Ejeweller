<?php

namespace App\Services\PostEx;

use App\Enums\OrderStatus;

/**
 * Map PostEx COD API v4.1.9 order statuses → shop OrderStatus.
 *
 * PostEx status IDs (get-order-status / get-all-order):
 * 1 Unbooked, 2 Booked, 3 PostEx WareHouse, 4 Out For Delivery,
 * 5 Delivered, 6 Returned, 7 Un-Assigned By Me, 8 Expired,
 * 9 Delivery Under Review, 15 Picked By PostEx, 16 Out For Return,
 * 17 Attempted, 18 En-Route to PostEx warehouse
 */
class PostExStatusMapper
{
    /**
     * @return int|null Shop OrderStatus constant, or null = no shop status change
     */
    public function mapToShopStatus(?string $postexStatus): ?int
    {
        $key = $this->normalize($postexStatus);
        if ($key === '') {
            return null;
        }

        if ($this->containsAny($key, ['delivered'])) {
            return OrderStatus::DELIVERED;
        }

        if ($this->containsAny($key, ['out for return', 'returned', 'return requested'])) {
            return OrderStatus::REJECTED;
        }

        // Merchant cancel API sets "Cancelled" locally — do not auto-cancel shop from that alone.
        // PostEx-side dead ends: Expired / Un-Assigned.
        if ($this->containsAny($key, [
            'expired',
            'un assigned by me',
            'unassigned by me',
            'un-assigned by me',
        ])) {
            return OrderStatus::CANCELED;
        }

        if ($this->containsAny($key, [
            'out for delivery',
            'attempted',
            'attempt made',
            'picked by postex',
            'en-route to postex warehouse',
            'en route to postex warehouse',
            'postex warehouse',
            'at postex warehouse',
            'delivery under review',
            'package on root',
            'package on route',
        ])) {
            return OrderStatus::ON_THE_WAY;
        }

        // Booked (not Unbooked)
        if (str_contains($key, 'booked') && !str_contains($key, 'unbooked') && !str_contains($key, 'un booked')) {
            return OrderStatus::CONFIRMED;
        }

        return null;
    }

    /**
     * Whether the shop order may move to the mapped status (no backward / terminal overwrite).
     */
    public function canApply(int $currentShopStatus, int $mappedStatus): bool
    {
        if ($currentShopStatus === $mappedStatus) {
            return false;
        }

        $terminal = [OrderStatus::DELIVERED, OrderStatus::CANCELED, OrderStatus::REJECTED];
        if (in_array($currentShopStatus, $terminal, true)) {
            return false;
        }

        $rank = [
            OrderStatus::PENDING    => 1,
            OrderStatus::CONFIRMED  => 2,
            OrderStatus::ON_THE_WAY => 3,
            OrderStatus::DELIVERED  => 4,
            OrderStatus::CANCELED   => 4,
            OrderStatus::REJECTED   => 4,
        ];

        // Terminal mapped statuses always allowed from non-terminal
        if (in_array($mappedStatus, [OrderStatus::DELIVERED, OrderStatus::CANCELED, OrderStatus::REJECTED], true)) {
            return true;
        }

        return ($rank[$mappedStatus] ?? 0) > ($rank[$currentShopStatus] ?? 0);
    }

    public function normalize(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $status = str_replace(['_', '-'], ' ', $status);
        $status = preg_replace('/\s+/', ' ', $status) ?? $status;

        return $status;
    }

    /**
     * @param  list<string>  $needles
     */
    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
