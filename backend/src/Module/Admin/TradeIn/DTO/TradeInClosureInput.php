<?php

declare(strict_types=1);

namespace App\Module\Admin\TradeIn\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TradeInClosureInput
{
    public function __construct(
        #[Assert\PositiveOrZero]
        public int $finalOfferCents,
        #[Assert\Choice(choices: ['bank_transfer', 'cash', 'store_credit', 'other'])]
        public string $paymentMethod,
        #[Assert\Choice(choices: ['pending', 'paid'])]
        public string $paymentStatus,
        #[Assert\Length(max: 120)]
        public ?string $transactionReference,
        #[Assert\Length(max: 5000)]
        public ?string $note,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $string = static fn (string $key): string => is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
        $nullableString = static fn (string $key): ?string => '' !== $string($key) ? $string($key) : null;

        return new self(
            is_numeric($payload['finalOfferCents'] ?? null) ? (int) $payload['finalOfferCents'] : 0,
            $string('paymentMethod'),
            $string('paymentStatus'),
            $nullableString('transactionReference'),
            $nullableString('note'),
        );
    }
}
