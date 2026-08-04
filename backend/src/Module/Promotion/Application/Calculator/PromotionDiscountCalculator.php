<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Calculator;

use App\Module\Promotion\Domain\Entity\Promotion;

final readonly class PromotionDiscountCalculator
{
    public function compute(Promotion $promotion, int $subtotalPriceCents): int
    {
        if ($subtotalPriceCents <= 0) {
            return 0;
        }

        if (Promotion::TYPE_PERCENT === $promotion->getDiscountType()) {
            $percent = max(0, min(100, $promotion->getDiscountValue()));

            return min($subtotalPriceCents, (int) round($subtotalPriceCents * ($percent / 100)));
        }

        return min($subtotalPriceCents, max(0, $promotion->getDiscountValue()));
    }
}
