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
    public function openCampaigns(int $limit = 20, int $offset = 0): array
    {
        $now = new \DateTimeImmutable();
        $this->closeElapsedActiveCampaigns($now);

        return $this->campaigns->findOpenForReports($now, $limit, $offset);
    }

    public function countOpenCampaigns(): int
    {
        $now = new \DateTimeImmutable();
        $this->closeElapsedActiveCampaigns($now);

        return $this->campaigns->countOpenForReports($now);
    }

    private function closeElapsedActiveCampaigns(\DateTimeImmutable $now): void
    {
        $hasClosedCampaign = false;
        $offset = 0;

        do {
            $campaigns = $this->campaigns->findBy(['status' => BetaCampaign::STATUS_ACTIVE], ['startsAt' => 'ASC'], 100, $offset);
            foreach ($campaigns as $campaign) {
                if ('closed' === $campaign->getEffectiveStatus($now)) {
                    $campaign->setStatus('closed');
                    $hasClosedCampaign = true;
                }
            }
            $offset += 100;
        } while (count($campaigns) === 100);

        if ($hasClosedCampaign) {
            $this->persistence->commit();
        }
    }
}
