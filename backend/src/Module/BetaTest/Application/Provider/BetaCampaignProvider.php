<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Provider;

use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Enum\BetaCampaignStatus;
use App\Shared\Application\UnitOfWork;
use Psr\Clock\ClockInterface;

final readonly class BetaCampaignProvider
{
    public function __construct(
        private BetaCampaignRepositoryPort $campaigns,
        private UnitOfWork $persistence,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<BetaCampaign>
     */
    public function openCampaigns(int $limit = 20, int $offset = 0): array
    {
        $now = $this->clock->now();
        $this->closeElapsedActiveCampaigns($now);

        return $this->campaigns->findOpenForReports($now, $limit, $offset);
    }

    public function countOpenCampaigns(): int
    {
        $now = $this->clock->now();
        $this->closeElapsedActiveCampaigns($now);

        return $this->campaigns->countOpenForReports($now);
    }

    private function closeElapsedActiveCampaigns(\DateTimeImmutable $now): void
    {
        $hasClosedCampaign = false;
        $offset = 0;

        do {
            $campaigns = $this->campaigns->findBy(['status' => BetaCampaignStatus::ACTIVE->value], ['startsAt' => 'ASC'], 100, $offset);
            foreach ($campaigns as $campaign) {
                if (BetaCampaignStatus::CLOSED->value === $campaign->getEffectiveStatus($now)) {
                    $campaign->setStatus(BetaCampaignStatus::CLOSED);
                    $hasClosedCampaign = true;
                }
            }
            $offset += 100;
        } while (100 === count($campaigns));

        if ($hasClosedCampaign) {
            $this->persistence->flush();
        }
    }
}
