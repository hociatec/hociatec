<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Service;

use App\Module\Promotion\Domain\Entity\Promotion;

final class PromotionFormatter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatPromotion(Promotion $promotion): array
    {
        return [
            'id' => $promotion->getId(),
            'name' => $promotion->getName(),
            'slug' => $promotion->getSlug(),
            'description' => $promotion->getDescription(),
            'discountType' => $promotion->getDiscountType(),
            'discountValue' => $promotion->getDiscountValue(),
            'audienceKey' => $promotion->getAudienceKey(),
            'criteria' => $promotion->getCriteria(),
            'isActive' => $promotion->isActive(),
            'startsAt' => $promotion->getStartsAt()?->format(DATE_ATOM),
            'endsAt' => $promotion->getEndsAt()?->format(DATE_ATOM),
            'createdAt' => $promotion->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $promotion->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
