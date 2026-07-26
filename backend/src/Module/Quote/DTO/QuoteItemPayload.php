<?php

declare(strict_types=1);

namespace App\Module\Quote\DTO;

final readonly class QuoteItemPayload
{
    public function __construct(
        public string $name,
        public ?int $productId,
        public ?int $serviceId,
        public ?int $unitPriceCents,
        public ?string $description,
        public ?string $unit,
        public int $quantity,
        public int $vatRateBps,
        public int $discountCents,
        public ?string $type,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['name'] ?? null) ? trim($payload['name']) : '',
            is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : null,
            is_numeric($payload['serviceId'] ?? null) ? (int) $payload['serviceId'] : null,
            is_numeric($payload['unitPriceCents'] ?? null) ? (int) $payload['unitPriceCents'] : null,
            is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            max(1, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1),
            max(0, is_numeric($payload['vatRateBps'] ?? null) ? (int) $payload['vatRateBps'] : 0),
            max(0, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : 0),
            is_string($payload['type'] ?? null) ? trim($payload['type']) : null,
        );
    }
}
