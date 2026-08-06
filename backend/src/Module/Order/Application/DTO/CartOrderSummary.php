<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

final readonly class CartOrderSummary
{
    public function __construct(
        public int $subtotalPriceCents,
        public int $discountAmountCents,
        public int $totalPriceCents,
        public ?string $appliedPromotionName,
        public ?string $appliedPromotionSlug,
    ) {
    }

    /** @param array<string, mixed> $summary */
    public static function fromArray(array $summary): self
    {
        return new self(
            (int) $summary['subtotalPriceCents'],
            (int) $summary['discountAmountCents'],
            (int) $summary['totalPriceCents'],
            self::nullableString($summary['appliedVoucher']['name'] ?? ($summary['appliedPromotion']['name'] ?? null)),
            self::nullableString($summary['appliedVoucher']['code'] ?? ($summary['appliedPromotion']['slug'] ?? null)),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? $value : null;
    }
}
