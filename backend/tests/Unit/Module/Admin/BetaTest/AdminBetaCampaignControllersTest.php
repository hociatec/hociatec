<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\BetaTest;

use App\Module\Admin\UI\BetaTest\Controller\CreateCampaignController;
use App\Module\Admin\UI\BetaTest\Controller\DeleteCampaignController;
use App\Module\Admin\UI\BetaTest\Controller\ListCampaignsController;
use App\Module\Admin\UI\BetaTest\Controller\UpdateCampaignController;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final class AdminBetaCampaignControllersTest extends AdminBetaTestIntegrationTestCase
{
    public function testCampaignControllers(): void
    {
        $em = $this->entityManager();
        [, , $campaign] = $this->seed($em);
        $persistence = new DoctrineUnitOfWork($em);

        $createCampaign = new CreateCampaignController($this->createCampaignHandler($persistence), $this->validator());
        self::assertSame(422, $createCampaign($this->jsonRequest(['name' => '', 'description' => 'Desc']))->getStatusCode());
        self::assertSame(422, $createCampaign($this->jsonRequest(['name' => 'Bad dates', 'description' => 'Desc', 'startsAt' => '2026-08-12', 'endsAt' => '2026-08-01']))->getStatusCode());
        $createdCampaign = $createCampaign($this->jsonRequest(['name' => 'Created', 'description' => 'Desc', 'startsAt' => 'bad', 'status' => 'weird']));
        self::assertSame(201, $createdCampaign->getStatusCode());
        $createdCampaignId = (int) json_decode((string) $createdCampaign->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $listCampaigns = new ListCampaignsController($this->campaigns($em), $this->profiles($em), $this->reports($em), $this->formatter(), $this->closeElapsedCampaignsHandler($persistence));
        self::assertSame(200, $listCampaigns()->getStatusCode());

        $updateCampaign = new UpdateCampaignController($this->campaigns($em), $this->updateCampaignHandler($persistence), $this->validator());
        self::assertSame(404, $updateCampaign(999, $this->jsonRequest(['name' => 'Nope'], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['name' => ''], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['description' => ''], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['startsAt' => '2026-08-12', 'endsAt' => '2026-08-01'], 'PATCH'))->getStatusCode());
        self::assertSame(200, $updateCampaign((int) $campaign->getId(), $this->jsonRequest(['name' => 'Updated', 'description' => 'Updated desc', 'status' => 'active', 'startsAt' => '', 'endsAt' => ''], 'PATCH'))->getStatusCode());

        $deleteCampaign = new DeleteCampaignController($this->campaigns($em), $this->deleteCampaignHandler($persistence));
        self::assertSame(404, $deleteCampaign(999)->getStatusCode());
        self::assertSame(200, $deleteCampaign($createdCampaignId)->getStatusCode());
    }
}
