<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderCheckoutPricingStateTrait
{
    public function getCurrencyCode(): string
    {
        return $this->pricing->currencyCode();
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        return $this->replacePricing(
            $this->pricing->subtotalPriceCents(),
            $this->pricing->discountAmountCents(),
            $this->pricing->totalPriceCents(),
            $currencyCode,
        );
    }

    public function replacePricing(int $subtotalPriceCents, int $discountAmountCents, int $totalPriceCents, string $currencyCode): self
    {
        $this->pricing->replaceAmounts($subtotalPriceCents, $discountAmountCents, $totalPriceCents, $currencyCode);

        return $this;
    }

    public function getSubtotalPriceCents(): int
    {
        return $this->pricing->subtotalPriceCents();
    }

    public function setSubtotalPriceCents(int $subtotalPriceCents): self
    {
        return $this->replacePricing($subtotalPriceCents, $this->pricing->discountAmountCents(), $this->pricing->totalPriceCents(), $this->pricing->currencyCode());
    }

    public function getDiscountAmountCents(): int
    {
        return $this->pricing->discountAmountCents();
    }

    public function setDiscountAmountCents(int $discountAmountCents): self
    {
        return $this->applyPromotion($this->pricing->appliedPromotionName(), $this->pricing->appliedPromotionSlug(), $discountAmountCents, $this->pricing->totalPriceCents());
    }

    public function getTotalPriceCents(): int
    {
        return $this->pricing->totalPriceCents();
    }

    public function setTotalPriceCents(int $totalPriceCents): self
    {
        return $this->applyPromotion($this->pricing->appliedPromotionName(), $this->pricing->appliedPromotionSlug(), $this->pricing->discountAmountCents(), $totalPriceCents);
    }

    public function getAppliedPromotionName(): ?string
    {
        return $this->pricing->appliedPromotionName();
    }

    public function setAppliedPromotionName(?string $appliedPromotionName): self
    {
        return $this->applyPromotion($appliedPromotionName, $this->pricing->appliedPromotionSlug(), $this->pricing->discountAmountCents(), $this->pricing->totalPriceCents());
    }

    public function getAppliedPromotionSlug(): ?string
    {
        return $this->pricing->appliedPromotionSlug();
    }

    public function setAppliedPromotionSlug(?string $appliedPromotionSlug): self
    {
        return $this->applyPromotion($this->pricing->appliedPromotionName(), $appliedPromotionSlug, $this->pricing->discountAmountCents(), $this->pricing->totalPriceCents());
    }

    public function applyPromotion(?string $name, ?string $slug, int $discountAmountCents, int $totalPriceCents): self
    {
        $this->pricing->applyPromotion($name, $slug, $discountAmountCents, $totalPriceCents);

        return $this;
    }
}
