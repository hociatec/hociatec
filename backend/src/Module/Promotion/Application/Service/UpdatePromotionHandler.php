<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Domain\Entity\Promotion;

final readonly class UpdatePromotionHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private PromotionDataApplier $dataApplier,
    ) {
    }

    public function update(Promotion $promotion, PromotionInput $input): Promotion
    {
        $this->dataApplier->apply($promotion, $input);
        $this->persistence->commit();

        return $promotion;
    }
}
