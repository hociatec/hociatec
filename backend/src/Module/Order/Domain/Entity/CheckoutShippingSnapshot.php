<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

final class CheckoutShippingSnapshot
{
    private ?string $shippingName;
    private ?string $shippingAddress;
    private ?string $shippingPostalCode;
    private ?string $shippingCity;

    public function __construct(?string $shippingName, ?string $shippingAddress, ?string $shippingPostalCode, ?string $shippingCity)
    {
        $this->shippingName = $shippingName;
        $this->shippingAddress = $shippingAddress;
        $this->shippingPostalCode = $shippingPostalCode;
        $this->shippingCity = $shippingCity;
    }

    public function name(): ?string
    {
        return $this->shippingName;
    }

    public function address(): ?string
    {
        return $this->shippingAddress;
    }

    public function postalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function city(): ?string
    {
        return $this->shippingCity;
    }
}
