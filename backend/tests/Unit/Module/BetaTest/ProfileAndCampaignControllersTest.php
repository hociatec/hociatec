<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest;

use App\Module\BetaTest\Application\Provider\BetaCampaignProvider;
use App\Module\BetaTest\Application\Workflow\BetaTesterProfileService;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\UI\Controller\GetMyBetaProfileController;
use App\Module\BetaTest\UI\Controller\LeaveBetaProgramController;
use App\Module\BetaTest\UI\Controller\ListBetaCampaignsController;
use App\Module\BetaTest\UI\Controller\UpdateMyBetaProfileController;
use App\Module\BetaTest\UI\Http\BetaCampaignResponseFormatter;
use App\Module\BetaTest\UI\Http\BetaProfileResponseFormatter;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProfileAndCampaignControllersTest extends BetaTestIntegrationTestCase
{
    public function testProfileAndCampaignControllersCoverMainBranches(): void
    {
        $em = $this->entityManager();
        $user = $this->user('beta@example.com');
        $em->persist($user);
        $em->flush();

        $profiles = $this->profiles($em);
        $campaigns = $this->campaigns($em);
        $persistence = new DoctrineUnitOfWork($em);

        $profileService = new BetaTesterProfileService($persistence, new MockClock('2026-07-26'));
        $list = new ListBetaCampaignsController($profiles, new BetaCampaignProvider($campaigns, $persistence, new MockClock('2026-08-11T10:00:00+00:00')), new BetaCampaignResponseFormatter());
        $list->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $list()->getStatusCode());
        $list->setContainer($this->container($user));
        self::assertSame([], json_decode((string) $list()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['items']);

        $get = new GetMyBetaProfileController($profiles, new BetaProfileResponseFormatter());
        $get->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $get()->getStatusCode());
        $get->setContainer($this->container($user));
        $emptyProfilePayload = json_decode((string) $get()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_OK, $get()->getStatusCode());
        self::assertNull($emptyProfilePayload['data']['profile']);

        $update = new UpdateMyBetaProfileController($profiles, $this->validator(2), $profileService);
        $update->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $update(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        $update->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $update(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        $profile = $profiles->findOneByUser($user);
        self::assertInstanceOf(BetaTesterProfile::class, $profile);
        $profile->setStatus(BetaTesterProfile::STATUS_ACCEPTED);

        $active = (new BetaCampaign('Active', 'Desc', new \DateTimeImmutable('2026-08-10T10:00:00+00:00'), new \DateTimeImmutable('2026-08-12T10:00:00+00:00')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $closed = (new BetaCampaign('Closed', 'Desc', new \DateTimeImmutable('2026-08-08T10:00:00+00:00'), new \DateTimeImmutable('2026-08-10T10:00:00+00:00')))->setStatus(BetaCampaign::STATUS_ACTIVE);
        $em->persist($active);
        $em->persist($closed);
        $em->flush();

        self::assertSame(Response::HTTP_OK, $get()->getStatusCode());
        $payload = json_decode((string) $list()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $payload['data']['items']);
        self::assertSame(BetaCampaign::STATUS_CLOSED, $closed->getStatus());

        self::assertSame(Response::HTTP_OK, $update(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(['motivation' => 'Updated']), JSON_THROW_ON_ERROR)))->getStatusCode());

        $leave = new LeaveBetaProgramController($profiles, $profileService);
        $leave->setContainer($this->container(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $leave()->getStatusCode());
        $leave->setContainer($this->container($user));
        self::assertSame(Response::HTTP_OK, $leave()->getStatusCode());
        self::assertNull($profiles->findOneByUser($user));
        self::assertSame(Response::HTTP_OK, $leave()->getStatusCode());
    }
}
