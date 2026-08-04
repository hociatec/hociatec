<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class UpdateBetaCampaignHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BetaCampaignPayloadMapper $payloadMapper,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function update(BetaCampaign $campaign, array $payload): BetaCampaign
    {
        $this->payloadMapper->update($campaign, $payload);
        $this->persistence->flush();

        return $campaign;
    }
}
