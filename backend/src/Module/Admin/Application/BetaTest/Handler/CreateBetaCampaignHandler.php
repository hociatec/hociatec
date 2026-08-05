<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\Admin\Application\BetaTest\DTO\CreateBetaCampaignInput;
use App\Module\Admin\Application\BetaTest\Mapper\BetaCampaignPayloadMapper;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Shared\Application\UnitOfWork;

final readonly class CreateBetaCampaignHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private BetaCampaignPayloadMapper $payloadMapper,
    ) {
    }

    public function create(CreateBetaCampaignInput $input): BetaCampaign
    {
        $campaign = $this->payloadMapper->create($input);
        $this->persistence->persist($campaign);
        $this->persistence->commit();

        return $campaign;
    }
}
