<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;

final readonly class BetaCampaignProvider
{
    public function __construct(
        private BetaCampaignRepository $campaigns,
        private DoctrineUnitOfWork $persistence,
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
