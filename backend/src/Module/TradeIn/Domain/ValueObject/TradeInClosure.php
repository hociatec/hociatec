<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInClosure
{
    public function __construct(
        public int $finalOfferCents,
        public string $paymentMethod,
        public string $paymentStatus,
        public ?string $transactionReference,
        public ?\DateTimeImmutable $paidAt,
    ) {
    }

    public static function fromInput(int $finalOfferCents, string $paymentMethod, string $paymentStatus, ?string $transactionReference, ?\DateTimeImmutable $paidAt): self
    {
        if ($finalOfferCents < 0) {
            throw new \InvalidArgumentException('Le montant final de reprise ne peut pas être négatif.');
        }

        return new self(
            $finalOfferCents,
            trim($paymentMethod),
            trim($paymentStatus),
            null !== $transactionReference && '' !== trim($transactionReference) ? trim($transactionReference) : null,
            $paidAt,
        );
    }
}
