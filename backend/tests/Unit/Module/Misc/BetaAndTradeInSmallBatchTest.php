<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\TradeIn\Controller\ShowTradeInController;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportActivityRepository;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class BetaAndTradeInSmallBatchTest extends TestCase
{
    public function testBetaRepositoriesAndTradeInShowController(): void
    {
        $user = $this->user();
        $this->setId($user, 3);
        $this->entityManager()->persist($user);

        $profile = new BetaTesterProfile($user, ['weekly'], 'motivation', 'advanced', 'high', 'expert', 'none', ['screen-reader'], ['mac'], ['chrome'], ['ui'], new \DateTimeImmutable('2026-07-01T10:00:00+00:00'), '2026-07-26');
        $this->setId($profile, 8);
        $this->entityManager()->persist($profile);

        $report = new BugReport($user, null, 'Bug', 'Body', null, null, 'low', '/beta');
        $this->setId($report, 5);
        $this->entityManager()->persist($report);
        $activity = new BugReportActivity($report, $user, 'created', null, null, 'hello');
        $this->entityManager()->persist($activity);

        $tradeIn = $this->tradeInRequest($user);
        $this->setId($tradeIn, 7);
        $this->entityManager()->persist($tradeIn);
        $this->entityManager()->flush();

        $profiles = $this->profileRepository();
        self::assertSame($profile->getId(), $profiles->findOneByUser($user)?->getId());

        $activities = $this->activityRepository()->findForReport($report);
        self::assertCount(1, $activities);
        self::assertSame('created', $activities[0]->getAction());

        $show = new ShowTradeInController($this->tradeInRepository(), new \App\Module\TradeIn\Application\Projection\TradeInFormatter());
        self::assertSame(Response::HTTP_NOT_FOUND, $show(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $show((int) $tradeIn->getId())->getStatusCode());
    }

    private ?EntityManager $em = null;

    private function entityManager(): EntityManager
    {
        if (null !== $this->em) {
            return $this->em;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->em = new EntityManager($connection, $config);
        $tool = new SchemaTool($this->em);
        $tool->createSchema([
            $this->em->getClassMetadata(User::class),
            $this->em->getClassMetadata(BetaTesterProfile::class),
            $this->em->getClassMetadata(BugReport::class),
            $this->em->getClassMetadata(BugReportActivity::class),
            $this->em->getClassMetadata(TradeInRequest::class),
        ]);

        return $this->em;
    }

    private function profileRepository(): BetaTesterProfileRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new BetaTesterProfileRepository($registry);
    }

    private function activityRepository(): BugReportActivityRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new BugReportActivityRepository($registry);
    }

    private function tradeInRepository(): TradeInRequestRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new TradeInRequestRepository($registry);
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function tradeInRequest(User $user): TradeInRequest
    {
        return TradeInRequest::fromLegacySubmittedScalars(
            'TR-1',
            $user,
            'Ada',
            'Lovelace',
            'ada@example.com',
            '0102030405',
            'smartphone',
            'iPhone',
            1000,
            2024,
            'Apple',
            '13',
            'SN',
            'bon',
            true,
            true,
            true,
            'Desc',
            null,
            null,
            100,
            200,
            new \DateTimeImmutable('2026-07-01T10:00:00+00:00'),
        );
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
