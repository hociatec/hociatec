<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Handler;

use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Application\Writer\PromotionDataApplier;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Shared\Application\UnitOfWork;

final readonly class UpdatePromotionHandler
{
    public function __construct(
        private UnitOfWork $persistence,
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
