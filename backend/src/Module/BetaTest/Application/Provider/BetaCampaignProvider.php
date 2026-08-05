<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Provider;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Shared\Application\UnitOfWork;

final readonly class BetaCampaignProvider
{
    public function __construct(
        private BetaCampaignRepositoryPort $campaigns,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @return list<BetaCampaign>
     */
    public function openCampaigns(): array
    {
        $now = new \DateTimeImmutable();
        $allActiveCampaigns = $this->campaigns->findBy(['status' => 'active'], ['startsAt' => 'ASC']);
        $hasClosedCampaign = false;

        foreach ($allActiveCampaigns as $campaign) {
            if ('closed' === $campaign->getEffectiveStatus($now)) {
                $campaign->setStatus('closed');
                $hasClosedCampaign = true;
            }
        }

        if ($hasClosedCampaign) {
            $this->persistence->commit();
        }

        return array_values(array_filter($allActiveCampaigns, static fn (BetaCampaign $campaign): bool => $campaign->isOpenForReports($now)));
    }
}
