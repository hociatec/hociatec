<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class CloseElapsedBetaCampaignsHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    /** @param list<BetaCampaign> $campaigns */
    public function closeElapsed(array $campaigns, \DateTimeImmutable $now): void
    {
        $hasClosedCampaign = false;
        foreach ($campaigns as $campaign) {
            if ('closed' === $campaign->getEffectiveStatus($now) && 'closed' !== $campaign->getStatus()) {
                $campaign->setStatus('closed');
                $hasClosedCampaign = true;
            }
        }

        if ($hasClosedCampaign) {
            $this->persistence->commit();
        }
    }
}
