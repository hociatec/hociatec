<?php

declare(strict_types=1);

namespace App\Module\Promotion\Service;

use App\Module\Promotion\DTO\PromotionInput;
use App\Module\Promotion\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PromotionManager
{
    public function __construct(private EntityManagerInterface $entityManager)
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
        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

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
        $this->entityManager->flush();

        return $promotion;
    }

    public function delete(Promotion $promotion): void
    {
        $this->entityManager->remove($promotion);
        $this->entityManager->flush();
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
