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
        return $this->replacePaymentAmounts($this->payment->getSubtotalPriceCents(), $this->payment->getDiscountAmountCents(), $cents);
    }

    public function getSubtotalPriceCents(): int
    {
        return $this->payment->getSubtotalPriceCents();
    }

    public function setSubtotalPriceCents(int $cents): self
    {
        return $this->replacePaymentAmounts($cents, $this->payment->getDiscountAmountCents(), $this->payment->getTotalPriceCents());
    }

    public function getDiscountAmountCents(): int
    {
        return $this->payment->getDiscountAmountCents();
    }

    public function setDiscountAmountCents(int $cents): self
    {
        return $this->applyPaymentPromotion($this->payment->getAppliedPromotionName(), $this->payment->getAppliedPromotionSlug(), $cents, $this->payment->getTotalPriceCents());
    }

    public function replacePaymentAmounts(int $subtotalPriceCents, int $discountAmountCents, int $totalPriceCents): self
    {
        $this->payment
            ->setSubtotalPriceCents($subtotalPriceCents)
            ->setDiscountAmountCents($discountAmountCents)
            ->setTotalPriceCents($totalPriceCents);

        return $this;
    }

    public function applyPaymentPromotion(?string $name, ?string $slug, int $discountAmountCents, int $totalPriceCents): self
    {
        $this->payment
            ->setAppliedPromotionName($name)
            ->setAppliedPromotionSlug($slug)
            ->setDiscountAmountCents($discountAmountCents)
            ->setTotalPriceCents($totalPriceCents);

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
        return $this->applyPaymentPromotion($name, $this->payment->getAppliedPromotionSlug(), $this->payment->getDiscountAmountCents(), $this->payment->getTotalPriceCents());
    }

    public function getAppliedPromotionSlug(): ?string
    {
        return $this->payment->getAppliedPromotionSlug();
    }

    public function setAppliedPromotionSlug(?string $slug): self
    {
        return $this->applyPaymentPromotion($this->payment->getAppliedPromotionName(), $slug, $this->payment->getDiscountAmountCents(), $this->payment->getTotalPriceCents());
    }
}
