<?php

namespace App\Analytics\DTOs;

use App\Analytics\Support\ProductUrlAttribution;
use Carbon\Carbon;

readonly class IngestEventDTO
{
    public function __construct(
        public string $eventUuid,
        public string $eventName,
        public string $eventCategory,
        public ?string $pageUrl,
        public ?string $pageTitle,
        public ?int $productId,
        public ?string $productSku,
        public ?float $revenue,
        public ?string $currency,
        public ?int $orderId,
        public array $properties,
        public Carbon $occurredAt,
    ) {}

    public static function fromArray(array $data): self
    {
        $occurred = isset($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        $pageUrl = $data['page_url'] ?? $data['url'] ?? null;
        $productId = isset($data['product_id']) ? (int) $data['product_id'] : null;
        if (!$productId) {
            $path = is_array($data['properties'] ?? null)
                ? ($data['properties']['path'] ?? null)
                : null;
            $productId = ProductUrlAttribution::productIdFromUrl($pageUrl)
                ?? ($path ? ProductUrlAttribution::productIdFromUrl('https://local' . $path) : null);
        }

        return new self(
            eventUuid: (string) ($data['event_uuid'] ?? $data['id'] ?? ''),
            eventName: (string) ($data['event_name'] ?? $data['name'] ?? 'unknown'),
            eventCategory: (string) ($data['event_category'] ?? $data['category'] ?? 'general'),
            pageUrl: $pageUrl,
            pageTitle: $data['page_title'] ?? $data['title'] ?? null,
            productId: $productId,
            productSku: $data['product_sku'] ?? $data['sku'] ?? null,
            revenue: isset($data['revenue']) ? (float) $data['revenue'] : null,
            currency: $data['currency'] ?? null,
            orderId: isset($data['order_id']) ? (int) $data['order_id'] : null,
            properties: is_array($data['properties'] ?? null) ? $data['properties'] : [],
            occurredAt: $occurred,
        );
    }

    public function toInsertArray(int $siteId, ?int $sessionId, ?int $visitorId): array
    {
        return [
            'site_id' => $siteId,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
            'event_uuid' => $this->eventUuid,
            'event_name' => $this->eventName,
            'event_category' => $this->eventCategory,
            'page_url' => $this->pageUrl ? mb_substr($this->pageUrl, 0, 2048) : null,
            'page_title' => $this->pageTitle ? mb_substr($this->pageTitle, 0, 512) : null,
            'product_id' => $this->productId,
            'product_sku' => $this->productSku,
            'revenue' => $this->revenue,
            'currency' => $this->currency,
            'order_id' => $this->orderId,
            'properties' => json_encode($this->properties),
            'occurred_at' => $this->occurredAt,
            'event_date' => $this->occurredAt->toDateString(),
            'ingested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
