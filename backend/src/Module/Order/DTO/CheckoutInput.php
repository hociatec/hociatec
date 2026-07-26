<?php

declare(strict_types=1);

namespace App\Module\Order\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CheckoutInput
{
    public function __construct(
        #[Assert\Positive]
        public ?int $addressId = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['addressId'] ?? null) ? (int) $payload['addressId'] : null,
        );
    }
}
