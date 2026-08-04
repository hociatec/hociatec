<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\DTO;

use App\Shared\Domain\ValueObject\Money;

final readonly class QuotePayload
{
    /**
     * @param array<string,mixed>    $customer
     * @param list<QuoteItemPayload> $items
     */
    public function __construct(
        public array $customer,
        public string $status,
        public Money $discount,
        public Money $shipping,
        public ?string $conditions,
        public ?string $validFrom,
        public ?string $validUntil,
        public array $items,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_array($payload['customer'] ?? null) ? $payload['customer'] : [],
            is_string($payload['status'] ?? null) ? trim($payload['status']) : 'draft',
            Money::fromCents(max(0, is_numeric($payload['discountCents'] ?? null) ? (int) $payload['discountCents'] : 0)),
            Money::fromCents(max(0, is_numeric($payload['shippingCents'] ?? null) ? (int) $payload['shippingCents'] : 0)),
            is_string($payload['conditions'] ?? null) ? trim($payload['conditions']) : null,
            is_string($payload['validFrom'] ?? null) ? trim($payload['validFrom']) : null,
            is_string($payload['validUntil'] ?? null) ? trim($payload['validUntil']) : null,
            is_array($payload['items'] ?? null)
                ? array_values(array_map(
                    static fn (mixed $item): QuoteItemPayload => QuoteItemPayload::fromArray(is_array($item) ? $item : []),
                    $payload['items'],
                ))
                : [],
        );
    }
}
