<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Module\Admin\Application\BetaTest\DTO\CreateBetaCampaignInput;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class CreateBetaCampaignHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
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
