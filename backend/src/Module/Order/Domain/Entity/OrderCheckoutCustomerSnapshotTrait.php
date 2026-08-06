<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderCheckoutCustomerSnapshotTrait
{
    public function getCustomerFullName(): ?string
    {
        return $this->customerSnapshot()->fullName();
    }

    public function setCustomerFullName(?string $customerFullName): self
    {
        $this->customerFullName = $customerFullName;

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerSnapshot()->email();
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }
}
