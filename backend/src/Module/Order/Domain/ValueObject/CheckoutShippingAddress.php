<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\ValueObject;

final readonly class CheckoutShippingAddress
{
    public function __construct(
        public ?string $name,
        public ?string $address,
        public ?string $postalCode,
        public ?string $city,
    ) {
    }
}
