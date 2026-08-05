<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CheckoutCustomerSnapshot
{
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerFullName = null;

    #[ORM\Column(length: 180)]
    private string $customerEmail;

    public function __construct(string $customerEmail = '')
    {
        $this->customerEmail = $customerEmail;
    }

    public function fullName(): ?string
    {
        return $this->customerFullName;
    }

    public function changeFullName(?string $customerFullName): void
    {
        $this->customerFullName = $customerFullName;
    }

    public function email(): string
    {
        return $this->customerEmail;
    }

    public function changeEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }
}
