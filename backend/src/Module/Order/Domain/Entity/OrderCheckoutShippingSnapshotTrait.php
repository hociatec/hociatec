<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderCheckoutShippingSnapshotTrait
{
    public function getShippingName(): ?string
    {
        return $this->shippingSnapshot()->name();
    }

    public function setShippingName(?string $shippingName): self
    {
        $this->shippingName = $shippingName;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingSnapshot()->address();
    }

    public function setShippingAddress(?string $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingSnapshot()->postalCode();
    }

    public function setShippingPostalCode(?string $shippingPostalCode): self
    {
        $this->shippingPostalCode = $shippingPostalCode;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingSnapshot()->city();
    }

    public function setShippingCity(?string $shippingCity): self
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }
}
