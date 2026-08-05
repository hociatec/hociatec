<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

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
