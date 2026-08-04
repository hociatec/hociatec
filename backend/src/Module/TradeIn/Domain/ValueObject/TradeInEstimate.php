<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInEstimate
{
    public function __construct(
        public int $minCents,
        public int $maxCents,
        public ?int $offerCents,
        public ?\DateTimeImmutable $offerExpiresAt,
    ) {
    }
}
