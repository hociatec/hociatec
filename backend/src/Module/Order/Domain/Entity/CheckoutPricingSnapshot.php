<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CheckoutPricingSnapshot
{
    #[ORM\Column(length: 3, enumType: CheckoutCurrency::class)]
    private CheckoutCurrency $currencyCode = CheckoutCurrency::EUR;

    #[ORM\Column(type: 'integer')]
    private int $subtotalPriceCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $discountAmountCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalPriceCents = 0;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionName = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionSlug = null;

    public function currencyCode(): string
    {
        return $this->currencyCode->value;
    }

    public function changeCurrencyCode(string $currencyCode): void
    {
        $this->currencyCode = CheckoutCurrency::fromCode($currencyCode);
    }

    public function subtotalPriceCents(): int
    {
        return $this->subtotalPriceCents;
    }

    public function changeSubtotalPriceCents(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Le sous-total ne peut pas etre negatif.');
        }

        $this->subtotalPriceCents = $amount;
    }

    public function discountAmountCents(): int
    {
        return $this->discountAmountCents;
    }

    public function changeDiscountAmountCents(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('La remise ne peut pas etre negative.');
        }

        $this->discountAmountCents = $amount;
    }

    public function totalPriceCents(): int
    {
        return $this->totalPriceCents;
    }

    public function changeTotalPriceCents(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Le total ne peut pas etre negatif.');
        }

        $this->totalPriceCents = $amount;
    }

    public function replaceAmounts(int $subtotalPriceCents, int $discountAmountCents, int $totalPriceCents, string $currencyCode): void
    {
        $this->changeCurrencyCode($currencyCode);
        $this->changeSubtotalPriceCents($subtotalPriceCents);
        $this->changeDiscountAmountCents($discountAmountCents);
        $this->changeTotalPriceCents($totalPriceCents);
    }

    public function applyPromotion(?string $name, ?string $slug, int $discountAmountCents, int $totalPriceCents): void
    {
        $this->changeDiscountAmountCents($discountAmountCents);
        $this->changeTotalPriceCents($totalPriceCents);
        $this->appliedPromotionName = null !== $name && '' !== trim($name) ? trim($name) : null;
        $this->appliedPromotionSlug = null !== $slug && '' !== trim($slug) ? trim($slug) : null;
    }

    public function appliedPromotionName(): ?string
    {
        return $this->appliedPromotionName;
    }

    public function changeAppliedPromotionName(?string $name): void
    {
        $this->appliedPromotionName = $name;
    }

    public function appliedPromotionSlug(): ?string
    {
        return $this->appliedPromotionSlug;
    }

    public function changeAppliedPromotionSlug(?string $slug): void
    {
        $this->appliedPromotionSlug = $slug;
    }
}
