<?php

declare(strict_types=1);

namespace App\Module\Cart\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCartItemInput
{
    public function __construct(
        #[Assert\PositiveOrZero] public int $quantity,
        #[Assert\Positive] public ?int $rentalMonths = null,
        #[Assert\Positive] public ?int $currentRentalMonths = null,
        #[Assert\Length(max: 64)] public ?string $cartToken = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : -1,
            is_numeric($payload['rentalMonths'] ?? null) ? (int) $payload['rentalMonths'] : null,
            is_numeric($payload['currentRentalMonths'] ?? null) ? (int) $payload['currentRentalMonths'] : null,
            is_string($payload['cartToken'] ?? null) ? trim($payload['cartToken']) : null,
        );
    }
}
