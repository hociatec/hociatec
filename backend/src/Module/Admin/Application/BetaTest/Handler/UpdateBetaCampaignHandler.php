<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\Admin\Application\BetaTest\DTO\UpdateBetaCampaignInput;
use App\Module\Admin\Application\BetaTest\Mapper\BetaCampaignPayloadMapper;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Shared\Application\UnitOfWork;

final readonly class UpdateBetaCampaignHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private BetaCampaignPayloadMapper $payloadMapper,
    ) {
    }

    public function update(BetaCampaign $campaign, UpdateBetaCampaignInput $input): BetaCampaign
    {
        $this->payloadMapper->update($campaign, $input);
        $this->persistence->flush();

        return $campaign;
    }
}
