<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\BetaTest;

use App\Module\Admin\UI\BetaTest\Controller\DeleteBetaTesterController;
use App\Module\Admin\UI\BetaTest\Controller\ExportBetaTestersController;
use App\Module\Admin\UI\BetaTest\Controller\ListBetaTestersController;
use App\Module\Admin\UI\BetaTest\Controller\UpdateBetaTesterController;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\HttpFoundation\Request;

final class AdminBetaTesterControllersTest extends AdminBetaTestIntegrationTestCase
{
    public function testBetaTesterControllers(): void
    {
        $em = $this->entityManager();
        [$reporter] = $this->seed($em);
        $persistence = new DoctrineUnitOfWork($em);

        $listTesters = new ListBetaTestersController($this->profiles($em));
        self::assertSame(200, $listTesters(Request::create('/?search=reporter&status=accepted&accessibility=none'))->getStatusCode());
        $testersExport = (new ExportBetaTestersController($this->profiles($em), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory()))();
        self::assertSame(200, $testersExport->getStatusCode());
        self::assertStringContainsString('beta-testeurs.csv', (string) $testersExport->headers->get('Content-Disposition'));

        $profile = $this->profiles($em)->findOneByUser($reporter);
        self::assertInstanceOf(BetaTesterProfile::class, $profile);
        $deleteProfileUser = $this->user('delete-profile-admin-beta@example.test');
        $deleteProfile = (new BetaTesterProfile($deleteProfileUser, ['weekdays'], 'Delete', 'manual', 'clear', 'advanced', 'none', ['nvda'], ['windows'], ['chrome'], ['ui'], new \DateTimeImmutable('2026-08-11T10:00:00+00:00'), '2026-08-04'))->setStatus(BetaTesterProfile::STATUS_PENDING);
        $em->persist($deleteProfileUser);
        $em->persist($deleteProfile);
        $em->flush();

        $updateTester = new UpdateBetaTesterController($this->profiles($em), $this->changeTesterStatusHandler($persistence));
        self::assertSame(404, $updateTester(999, $this->jsonRequest(['status' => 'accepted'], 'PATCH'))->getStatusCode());
        self::assertSame(422, $updateTester((int) $profile->getId(), $this->jsonRequest(['status' => 'bad'], 'PATCH'))->getStatusCode());
        self::assertSame(200, $updateTester((int) $profile->getId(), $this->jsonRequest(['status' => 'paused'], 'PATCH'))->getStatusCode());

        $deleteTester = new DeleteBetaTesterController($this->profiles($em), $this->deleteTesterHandler($persistence));
        self::assertSame(404, $deleteTester(999)->getStatusCode());
        self::assertSame(200, $deleteTester((int) $deleteProfile->getId())->getStatusCode());
    }
}
