<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class CreateBetaCampaignHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BetaCampaignPayloadMapper $payloadMapper,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): BetaCampaign
    {
        $campaign = $this->payloadMapper->create($payload);
        $this->persistence->persist($campaign);
        $this->persistence->commit();

        return $campaign;
    }
}
