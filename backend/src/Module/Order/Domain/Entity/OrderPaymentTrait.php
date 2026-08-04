<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderPaymentTrait
{
    public function getTotalPriceCents(): int
    {
        return $this->payment->getTotalPriceCents();
    }

    public function setTotalPriceCents(int $cents): self
    {
        $this->payment->setTotalPriceCents($cents);

        return $this;
    }

    public function getSubtotalPriceCents(): int
    {
        return $this->payment->getSubtotalPriceCents();
    }

    public function setSubtotalPriceCents(int $cents): self
    {
        $this->payment->setSubtotalPriceCents($cents);

        return $this;
    }

    public function getDiscountAmountCents(): int
    {
        return $this->payment->getDiscountAmountCents();
    }

    public function setDiscountAmountCents(int $cents): self
    {
        $this->payment->setDiscountAmountCents($cents);

        return $this;
    }

    public function getLoyaltyPointsAwarded(): int
    {
        return $this->payment->getLoyaltyPointsAwarded();
    }

    public function setLoyaltyPointsAwarded(int $points): self
    {
        $this->payment->setLoyaltyPointsAwarded($points);

        return $this;
    }

    public function getAppliedPromotionName(): ?string
    {
        return $this->payment->getAppliedPromotionName();
    }

    public function setAppliedPromotionName(?string $name): self
    {
        $this->payment->setAppliedPromotionName($name);

        return $this;
    }

    public function getAppliedPromotionSlug(): ?string
    {
        return $this->payment->getAppliedPromotionSlug();
    }

    public function setAppliedPromotionSlug(?string $slug): self
    {
        $this->payment->setAppliedPromotionSlug($slug);

        return $this;
    }
}
