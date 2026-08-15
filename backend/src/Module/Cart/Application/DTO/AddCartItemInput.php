<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddCartItemInput
{
    public function __construct(
        #[Assert\Positive] public int $productId,
        #[Assert\Positive] public int $quantity = 1,
        #[Assert\Choice(['sale', 'rental'])] public ?string $sellingType = null,
        #[Assert\Positive] public ?int $rentalMonths = null,
        public ?string $rentalStartDate = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : 0,
            is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1,
            is_string($payload['sellingType'] ?? null) ? strtolower(trim((string) $payload['sellingType'])) : null,
            is_numeric($payload['rentalMonths'] ?? null) ? (int) $payload['rentalMonths'] : null,
            is_string($payload['rentalStartDate'] ?? null) ? trim((string) $payload['rentalStartDate']) : null,
        );
    }
}
