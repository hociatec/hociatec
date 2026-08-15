<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCartItemInput
{
    public function __construct(
        #[Assert\PositiveOrZero] public int $quantity,
        #[Assert\Positive] public ?int $rentalMonths = null,
        #[Assert\Positive] public ?int $currentRentalMonths = null,
        public ?string $rentalStartDate = null,
        public ?string $currentRentalStartDate = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : -1,
            is_numeric($payload['rentalMonths'] ?? null) ? (int) $payload['rentalMonths'] : null,
            is_numeric($payload['currentRentalMonths'] ?? null) ? (int) $payload['currentRentalMonths'] : null,
            is_string($payload['rentalStartDate'] ?? null) ? trim((string) $payload['rentalStartDate']) : null,
            is_string($payload['currentRentalStartDate'] ?? null) ? trim((string) $payload['currentRentalStartDate']) : null,
        );
    }
}
