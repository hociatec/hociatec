<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Domain\Entity\Promotion;

final readonly class PromotionManager
{
    public function __construct(private DoctrinePersistence $persistence)
    {
    }

    public function create(PromotionInput $input): Promotion
    {
        $promotion = new Promotion(
            $input->name,
            $input->slug,
            $input->discountType,
            $input->discountValue,
            $input->audienceKey,
            $input->criteria,
        );

        $this->apply($promotion, $input);
        $this->persistence->persist($promotion);
        $this->persistence->flush();

        return $promotion;
    }

    public function update(Promotion $promotion, PromotionInput $input): Promotion
    {
        $promotion
            ->setName($input->name)
            ->setSlug($input->slug)
            ->setDiscountType($input->discountType)
            ->setDiscountValue($input->discountValue)
            ->setAudienceKey($input->audienceKey)
            ->setCriteria($input->criteria);

        $this->apply($promotion, $input);
        $this->persistence->flush();

        return $promotion;
    }

    public function delete(Promotion $promotion): void
    {
        $this->persistence->remove($promotion);
        $this->persistence->flush();
    }

    private function apply(Promotion $promotion, PromotionInput $input): void
    {
        $promotion
            ->setDescription($input->description)
            ->setIsActive($input->isActive)
            ->setStartsAt($input->startsAt)
            ->setEndsAt($input->endsAt);
    }
}
