<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteBetaCampaignHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function delete(BetaCampaign $campaign): void
    {
        $this->persistence->remove($campaign);
        $this->persistence->flush();
    }
}
