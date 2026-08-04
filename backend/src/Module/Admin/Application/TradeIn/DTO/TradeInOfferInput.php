<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TradeInOfferInput
{
    public function __construct(
        #[Assert\NotNull, Assert\PositiveOrZero] public ?int $offerCents,
        #[Assert\Length(max: 5000)] public ?string $adminNote,
        public ?string $offerExpiresAt,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_numeric($payload['offerCents'] ?? null) ? (int) $payload['offerCents'] : null, is_string($payload['adminNote'] ?? null) ? trim($payload['adminNote']) : null, is_string($payload['offerExpiresAt'] ?? null) ? trim($payload['offerExpiresAt']) : null);
    }
}
