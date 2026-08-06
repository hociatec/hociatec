<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\ValueObject;

final readonly class CheckoutBillingAddress
{
    public function __construct(
        public ?string $address,
        public ?string $postalCode,
        public ?string $city,
    ) {
    }
}
