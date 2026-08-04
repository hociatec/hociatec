<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Admin\Application\BetaTest\DTO\UpdateBetaCampaignInput;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;

final readonly class UpdateBetaCampaignHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BetaCampaignPayloadMapper $payloadMapper,
    ) {
    }

    public function update(BetaCampaign $campaign, UpdateBetaCampaignInput $input): BetaCampaign
    {
        $this->payloadMapper->update($campaign, $input);
        $this->persistence->commit();

        return $campaign;
    }
}
