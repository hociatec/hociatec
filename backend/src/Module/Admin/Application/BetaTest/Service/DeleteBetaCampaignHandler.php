<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class DeleteBetaCampaignHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function delete(BetaCampaign $campaign): void
    {
        $this->persistence->remove($campaign);
        $this->persistence->commit();
    }
}
