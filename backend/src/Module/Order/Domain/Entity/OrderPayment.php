<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderPayment
{
    #[ORM\Column(type: 'integer')]
    private int $totalPriceCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $subtotalPriceCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $discountAmountCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $loyaltyPointsAwarded = 0;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionName = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionSlug = null;

    public function getTotalPriceCents(): int
    {
        return $this->totalPriceCents;
    }

    public function setTotalPriceCents(int $cents): self
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Le total de paiement ne peut pas être négatif.');
        }

        $this->totalPriceCents = $cents;

        return $this;
    }

    public function getSubtotalPriceCents(): int
    {
        return $this->subtotalPriceCents;
    }

    public function setSubtotalPriceCents(int $cents): self
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Le sous-total de paiement ne peut pas être négatif.');
        }

        $this->subtotalPriceCents = $cents;

        return $this;
    }

    public function getDiscountAmountCents(): int
    {
        return $this->discountAmountCents;
    }

    public function setDiscountAmountCents(int $cents): self
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('La remise de paiement ne peut pas être négative.');
        }

        $this->discountAmountCents = $cents;

        return $this;
    }

    public function getLoyaltyPointsAwarded(): int
    {
        return $this->loyaltyPointsAwarded;
    }

    public function setLoyaltyPointsAwarded(int $points): self
    {
        if ($points < 0) {
            throw new \InvalidArgumentException('Les points de fidélité ne peuvent pas être négatifs.');
        }

        $this->loyaltyPointsAwarded = $points;

        return $this;
    }

    public function getAppliedPromotionName(): ?string
    {
        return $this->appliedPromotionName;
    }

    public function setAppliedPromotionName(?string $name): self
    {
        $this->appliedPromotionName = $name;

        return $this;
    }

    public function getAppliedPromotionSlug(): ?string
    {
        return $this->appliedPromotionSlug;
    }

    public function setAppliedPromotionSlug(?string $slug): self
    {
        $this->appliedPromotionSlug = $slug;

        return $this;
    }
}
