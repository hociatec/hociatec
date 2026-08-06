<?php

declare(strict_types=1);

namespace App\Module\Quote\Domain\Entity;

use App\Shared\Domain\ValueObject\Money;

trait QuoteCommercialTermsTrait
{
    public function getGlobalDiscountCents(): int
    {
        return $this->globalDiscountCents;
    }

    public function setGlobalDiscountCents(int $cents): self
    {
        return $this->applyCommercialTerms($cents, $this->shippingCents);
    }

    public function getShippingCents(): int
    {
        return $this->shippingCents;
    }

    public function setShippingCents(int $cents): self
    {
        return $this->applyCommercialTerms($this->globalDiscountCents, $cents);
    }

    public function applyCommercialTerms(int $globalDiscountCents, int $shippingCents): self
    {
        $this->globalDiscountCents = Money::fromCents($globalDiscountCents)->cents();
        $this->shippingCents = Money::fromCents($shippingCents)->cents();

        return $this;
    }
}
