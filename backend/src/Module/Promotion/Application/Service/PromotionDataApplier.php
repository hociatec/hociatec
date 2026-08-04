<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Service;

use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Domain\Entity\Promotion;

final readonly class PromotionDataApplier
{
    public function apply(Promotion $promotion, PromotionInput $input): void
    {
        $promotion
            ->setName($input->name)
            ->setSlug($input->slug)
            ->setDiscountType($input->discountType)
            ->setDiscountValue($input->discountValue)
            ->setAudienceKey($input->audienceKey)
            ->setCriteria($input->criteria)
            ->setDescription($input->description)
            ->setIsActive($input->isActive)
            ->setStartsAt($input->startsAt)
            ->setEndsAt($input->endsAt);
    }
}
