<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity\Concern;

use App\Shared\Domain\ValueObject\Money;

trait OrderItemPricingConcern
{
    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
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

    public function getLineSubtotalCents(): int
    {
        return $this->lineSubtotalCents;
    }

    public function setLineSubtotalCents(int $lineSubtotalCents): self
    {
        $this->lineSubtotalCents = Money::fromCents($lineSubtotalCents)->cents();

        return $this;
    }

    public function getLineVatCents(): int
    {
        return $this->lineVatCents;
    }

    public function setLineVatCents(int $lineVatCents): self
    {
        $this->lineVatCents = Money::fromCents($lineVatCents)->cents();

        return $this;
    }

    public function getLineTotalCents(): int
    {
        return $this->lineTotalCents;
    }

    public function setLineTotalCents(int $lineTotalCents): self
    {
        $this->lineTotalCents = Money::fromCents($lineTotalCents)->cents();

        return $this;
    }

    public function replaceLineTotals(int $lineSubtotalCents, int $lineVatCents, int $lineTotalCents): self
    {
        return $this
            ->setLineSubtotalCents($lineSubtotalCents)
            ->setLineVatCents($lineVatCents)
            ->setLineTotalCents($lineTotalCents);
    }

    public function getLinePriceCents(): int
    {
        return $this->lineTotalCents > 0 ? $this->lineTotalCents : $this->unitPriceCents * $this->quantity;
    }
}
