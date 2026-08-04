<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

final readonly class QuoteItemAddition
{
    public function __construct(
        public ?string $name,
        public ?int $unitPriceCents,
        public ?string $description,
        public ?string $unit,
        public int $quantity,
        public ?float $vatRate,
        public ?int $vatRateBps,
        public ?int $discountCents,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['name'] ?? null) && '' !== trim($payload['name']) ? trim($payload['name']) : null,
            is_numeric($payload['unitPriceCents'] ?? null) ? max(0, (int) $payload['unitPriceCents']) : null,
            is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            is_string($payload['unit'] ?? null) ? trim($payload['unit']) : null,
            max(1, is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1),
            is_numeric($payload['vatRate'] ?? null) ? (float) $payload['vatRate'] : null,
            is_numeric($payload['vatRateBps'] ?? null) ? max(0, (int) $payload['vatRateBps']) : null,
            is_numeric($payload['discountCents'] ?? null) ? max(0, (int) $payload['discountCents']) : null,
        );
    }
}
