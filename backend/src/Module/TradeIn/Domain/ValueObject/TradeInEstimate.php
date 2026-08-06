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
        if ($minCents < 0 || $maxCents < 0) {
            throw new \InvalidArgumentException('Les estimations de reprise ne peuvent pas être négatives.');
        }
        if ($maxCents < $minCents) {
            throw new \InvalidArgumentException('L’estimation maximale doit être supérieure ou égale à l’estimation minimale.');
        }
        if (null !== $offerCents && $offerCents < 0) {
            throw new \InvalidArgumentException('Le montant de l’offre ne peut pas être négatif.');
        }
    }
}
