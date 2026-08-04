<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Promotion\Domain\Entity\Promotion;

final readonly class DeletePromotionHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function delete(Promotion $promotion): void
    {
        $this->persistence->remove($promotion);
        $this->persistence->commit();
    }
}
