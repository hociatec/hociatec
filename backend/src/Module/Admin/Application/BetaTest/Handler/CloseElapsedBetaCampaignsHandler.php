<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Enum\BetaCampaignStatus;
use App\Shared\Application\UnitOfWork;

final readonly class CloseElapsedBetaCampaignsHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    /** @param list<BetaCampaign> $campaigns */
    public function closeElapsed(array $campaigns, \DateTimeImmutable $now): void
    {
        $hasClosedCampaign = false;
        foreach ($campaigns as $campaign) {
            if (
                BetaCampaignStatus::CLOSED->value === $campaign->getEffectiveStatus($now)
                && BetaCampaignStatus::CLOSED->value !== $campaign->getStatus()
            ) {
                $campaign->setStatus(BetaCampaignStatus::CLOSED);
                $hasClosedCampaign = true;
            }
        }

        if ($hasClosedCampaign) {
            $this->persistence->commit();
        }
    }
}
