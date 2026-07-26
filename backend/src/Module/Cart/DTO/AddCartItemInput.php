<?php

declare(strict_types=1);

namespace App\Module\Cart\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddCartItemInput
{
    public function __construct(
        #[Assert\Positive] public int $productId,
        #[Assert\Positive] public int $quantity = 1,
        #[Assert\Positive] public ?int $rentalMonths = null,
        #[Assert\Length(max: 64)] public ?string $cartToken = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['productId'] ?? null) ? (int) $payload['productId'] : 0,
            is_numeric($payload['quantity'] ?? null) ? (int) $payload['quantity'] : 1,
            is_numeric($payload['rentalMonths'] ?? null) ? (int) $payload['rentalMonths'] : null,
            is_string($payload['cartToken'] ?? null) ? trim($payload['cartToken']) : null,
        );
    }
}
