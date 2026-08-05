<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

final class CheckoutCustomerSnapshot
{
    private ?string $customerFullName;
    private string $customerEmail;

    public function __construct(?string $customerFullName, string $customerEmail)
    {
        $this->customerFullName = $customerFullName;
        $this->customerEmail = $customerEmail;
    }

    public function fullName(): ?string
    {
        return $this->customerFullName;
    }

    public function email(): string
    {
        return $this->customerEmail;
    }
}
