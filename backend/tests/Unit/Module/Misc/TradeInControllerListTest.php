<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\TradeIn\Controller\ListTradeInsController;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Tests\Support\TradeInRequestFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class TradeInControllerListTest extends TestCase
{
    public function testAdminAndUserTradeInListControllersReturnItems(): void
    {
        $user = $this->user();
        $this->setId($user, 12);
        $request = $this->tradeInRequest($user);
        $this->setId($request, 7);
        $otherUser = new User('grace@example.com', 'Grace', 'Hopper', new \DateTimeImmutable('1985-01-01'), '0607080910', 'female');
        $otherUser->setPassword('hashed');
        $this->setId($otherUser, 13);
        $otherRequest = TradeInRequestFactory::submitted(
            'TR-2',
            $otherUser,
            'Grace',
            'Hopper',
            'grace@example.com',
            '0607080910',
            'ordinateur',
            'ThinkPad',
            1500,
            2023,
            'Lenovo',
            'X1',
            'SN2',
            'tres_bon',
            true,
            true,
            true,
            'Desc 2',
            null,
            null,
            150,
            250,
            new \DateTimeImmutable('2026-07-02T10:00:00+00:00'),
        );
        $otherRequest->setStatus(TradeInStatus::COMPLETED);
        $this->setId($otherRequest, 8);
        $this->entityManager()->persist($user);
        $this->entityManager()->persist($otherUser);
        $this->entityManager()->persist($request);
        $this->entityManager()->persist($otherRequest);
        $this->entityManager()->flush();

        $repository = $this->repository();
        self::assertCount(1, $repository->findByUser($user));
        self::assertCount(2, $repository->findForAdmin());
        self::assertCount(1, $repository->findForAdmin(' iPhone ', TradeInStatus::SUBMITTED));
        self::assertCount(1, $repository->findForAdmin('Think', TradeInStatus::COMPLETED));

        $admin = new ListTradeInsController($repository, new \App\Module\TradeIn\Application\Projection\TradeInFormatter());
        $adminPayload = json_decode((string) $admin(new Request(['q' => 'iPhone', 'status' => TradeInStatus::SUBMITTED->value]))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $adminPayload['data']['items']);
        self::assertSame('TR-1', $adminPayload['data']['items'][0]['reference']);
    }

    private ?EntityManager $em = null;

    private function entityManager(): EntityManager
    {
        if (null !== $this->em) {
            return $this->em;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->em = new EntityManager($connection, $config);
        $tool = new SchemaTool($this->em);
        $tool->createSchema([
            $this->em->getClassMetadata(User::class),
            $this->em->getClassMetadata(TradeInRequest::class),
        ]);

        return $this->em;
    }

    private function repository(): TradeInRequestRepository
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
        return TradeInRequestFactory::submitted(
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
