<?php

declare(strict_types=1);

namespace App\Module\Service\Domain\Entity\Concern;

use App\Shared\Domain\ValueObject\Money;

trait ServiceOfferingBillingConcern
{
    public function getDurationValue(): ?int
    {
        return $this->durationValue;
    }

    public function setDurationValue(?int $durationValue): self
    {
        $this->durationValue = $durationValue;

        return $this;
    }

    public function getDurationUnit(): ?string
    {
        return $this->durationUnit;
    }

    public function setDurationUnit(?string $durationUnit): self
    {
        $this->durationUnit = $this->normalizeOptionalText($durationUnit);

        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): self
    {
        $this->priceCents = Money::fromCents($priceCents)->cents();

        return $this;
    }

    public function getVatRateBps(): int
    {
        return $this->vatRateBps;
    }

    public function setVatRateBps(int $vatRateBps): self
    {
        if ($vatRateBps < 0) {
            throw new \InvalidArgumentException('Le taux de TVA ne peut pas être négatif.');
        }

        $this->vatRateBps = $vatRateBps;

        return $this;
    }
}
