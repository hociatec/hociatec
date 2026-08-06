<?php

declare(strict_types=1);

namespace App\Module\Quote\Domain\Entity;

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
        if ($globalDiscountCents < 0) {
            throw new \InvalidArgumentException('La remise globale ne peut pas être négative.');
        }

        if ($shippingCents < 0) {
            throw new \InvalidArgumentException('Les frais de livraison ne peuvent pas être négatifs.');
        }

        $this->globalDiscountCents = $globalDiscountCents;
        $this->shippingCents = $shippingCents;

        return $this;
    }
}
