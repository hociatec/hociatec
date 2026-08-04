<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Service;

use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class CreatePromotionHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private PromotionDataApplier $dataApplier,
    ) {
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

        $this->dataApplier->apply($promotion, $input);
        $this->persistence->persist($promotion);
        $this->persistence->commit();

        return $promotion;
    }
}
