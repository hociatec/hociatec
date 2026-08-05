<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CheckoutShippingSnapshot
{
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $shippingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCity = null;

    public function name(): ?string
    {
        return $this->shippingName;
    }

    public function changeName(?string $shippingName): void
    {
        $this->shippingName = $shippingName;
    }

    public function address(): ?string
    {
        return $this->shippingAddress;
    }

    public function changeAddress(?string $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function postalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function changePostalCode(?string $shippingPostalCode): void
    {
        $this->shippingPostalCode = $shippingPostalCode;
    }

    public function city(): ?string
    {
        return $this->shippingCity;
    }

    public function changeCity(?string $shippingCity): void
    {
        $this->shippingCity = $shippingCity;
    }
}
