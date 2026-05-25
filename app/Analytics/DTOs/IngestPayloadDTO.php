<?php

namespace App\Analytics\DTOs;

readonly class IngestPayloadDTO
{
    /** @param IngestEventDTO[] $events */
    public function __construct(
        public string $sessionUuid,
        public string $visitorUuid,
        public ?int $userId,
        public array $events,
        public array $context,
    ) {}

    public static function fromArray(array $data): self
    {
        $events = [];
        foreach ($data['events'] ?? [] as $event) {
            if (is_array($event)) {
                $events[] = IngestEventDTO::fromArray($event);
            }
        }

        return new self(
            sessionUuid: (string) ($data['session_id'] ?? $data['session_uuid'] ?? ''),
            visitorUuid: (string) ($data['visitor_id'] ?? $data['visitor_uuid'] ?? ''),
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            events: $events,
            context: is_array($data['context'] ?? null) ? $data['context'] : [],
        );
    }
}
