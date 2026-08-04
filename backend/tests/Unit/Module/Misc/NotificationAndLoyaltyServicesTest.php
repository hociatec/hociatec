<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Service\AccountNotificationFormatter;
use App\Module\Notification\Application\Service\AccountNotificationProvider;
use App\Module\Notification\Application\Service\AccountNotificationReadStateService;
use App\Module\Notification\Application\Service\CommunicationPreferences;
use App\Module\Notification\Application\Service\ComputedAccountNotificationProviderInterface;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\User\Application\Service\UserPersistence;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use App\Module\Voucher\Application\Service\VoucherManager;
use App\Module\Loyalty\Application\Service\LoyaltyService;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotificationAndLoyaltyServicesTest extends TestCase
{
    public function testAccountNotificationFormatterAndProvider(): void
    {
        $user = $this->user();
        $user->setCommunicationPreferences([CommunicationPreferences::EMAIL, CommunicationPreferences::NOTIFICATION]);
        $user->setPassword('hashed');
        $this->setId($user, 7);

        $formatter = new AccountNotificationFormatter();
        self::assertSame('/mon-espace', $formatter->safeInternalTarget('https://evil.example'));
        self::assertSame('/orders/1', $formatter->safeInternalTarget(' /orders/1 '));
        self::assertSame('29/07/2026 10:00', $formatter->formatFrenchDateTime(new \DateTimeImmutable('2026-07-29T10:00:00+00:00')));
        self::assertSame('/orders/1', $formatter->computedNotification('a', 'b', 'c', '/orders/1', 'info', new \DateTimeImmutable('2026-07-29T10:00:00+00:00'))['to']);

        $eventRepository = $this->notificationRepository();
        $computedProvider = $this->createMock(ComputedAccountNotificationProviderInterface::class);
        $event = new AccountNotificationEvent($user, 'k1', 'Title', 'Message', '/target', 'order');
        $this->notificationEntityManager()->persist($user);
        $this->notificationEntityManager()->persist($event);
        $this->notificationEntityManager()->flush();
        $computedProvider->expects(self::exactly(2))->method('provide')->willReturn([
            ['key' => 'computed', 'label' => 'Computed', 'message' => 'Body', 'to' => '/mon-espace', 'type' => 'info', 'createdAt' => '2026-07-29T10:00:00+00:00'],
        ]);

        $provider = new AccountNotificationProvider($eventRepository, $formatter, [$computedProvider]);
        $notifications = $provider->provideForUser($user);

        self::assertCount(2, $notifications);
        self::assertSame('computed', $notifications[0]['key']);
        self::assertSame('k1', $notifications[1]['key']);
        self::assertSame('/target', $notifications[1]['to']);
        self::assertSame(2, $provider->countForUser($user));
        self::assertCount(0, $provider->provideForUser($user, 30, 10));

        $disabledUser = $this->user();
        $disabledUser->setCommunicationPreferences([CommunicationPreferences::EMAIL]);
        self::assertSame([], $provider->provideForUser($disabledUser));
        self::assertSame(0, $provider->countForUser($disabledUser));
    }

    public function testAccountNotificationReadStateServiceReadsAndUpdatesState(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(4))->method('flush');
        $service = new AccountNotificationReadStateService(new UserPersistence($entityManager));
        $user = $this->user();

        self::assertSame(['seenKeys' => [], 'dismissedKeys' => [], 'seenSignature' => ''], $service->read($user));

        $updated = $service->update($user, new NotificationReadStateInput(['a', 'b'], null, null, null));
        self::assertSame(['a', 'b'], $updated['seenKeys']);

        $updated = $service->update($user, new NotificationReadStateInput(null, ' c ', ['d', 'a'], null));
        self::assertSame(['a', 'b', 'c', 'd'], $updated['seenKeys']);
        self::assertSame(['c', 'd', 'a'], $updated['dismissedKeys']);

        $user->setAccountNotificationsSeenSignature("x\ny\n");
        self::assertSame(['x', 'y'], $service->read($user)['seenKeys']);

        $user->setAccountNotificationsSeenSignature('{bad json');
        self::assertSame(['{bad json'], $service->read($user)['seenKeys']);

        $user->setAccountNotificationsSeenSignature('"plain-string"');
        self::assertSame(['seenKeys' => [], 'dismissedKeys' => [], 'seenSignature' => ''], $service->read($user));

        $user->setAccountNotificationsSeenSignature(json_encode(['seenKeys' => ['z', 'z', ''], 'dismissedKeys' => ['w']], JSON_THROW_ON_ERROR));
        $updated = $service->update($user, new NotificationReadStateInput(null, null, null, "k1\nk2\n"));
        self::assertSame(['z', 'k1', 'k2'], $updated['seenKeys']);
        self::assertSame(['w'], $updated['dismissedKeys']);

        $updated = $service->update($user, new NotificationReadStateInput([str_repeat('a', 256), ' ok '], null, [123, ' ok ', ' two '], null));
        self::assertSame(['z', 'k1', 'k2', 'ok', 'two'], $updated['seenKeys']);
        self::assertSame(['w', 'ok', 'two'], $updated['dismissedKeys']);
    }

    public function testLoyaltyServiceConvertsPointsAndBuildsVoucher(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $entityManager->expects(self::atLeast(1))->method('persist');
        $entityManager->expects(self::atLeast(1))->method('flush');
        $persistence = new DoctrineUnitOfWork($entityManager);

        $voucherRepository = $this->voucherRepository();
        $voucherManager = new VoucherManager($voucherRepository, $persistence);
        $users = $this->createMock(UserRepository::class);
        $service = new LoyaltyService($persistence, new DoctrineTransactionManager($entityManager), $voucherManager, $users);

        self::assertSame(500, $service->pointsToCents(599));
        self::assertSame(500, $service->centsToPoints(599));

        $user = $this->user();
        $this->setId($user, 9);
        $user->setLoyaltyPointsBalance(2000);
        $accentedLockedUser = new User('client@example.com', 'Client', 'Elephant !!!', new \DateTimeImmutable('1990-01-01'), '0102030405', 'other');
        $this->setId($accentedLockedUser, 19);
        $accentedLockedUser->setLoyaltyPointsBalance(100);
        $entityManager->method('find')->willReturnCallback(static fn (string $class, int $id, int $lockMode): ?User => match ($id) {
            9 => $user,
            19 => $accentedLockedUser,
            default => null,
        });
        $order = new Order('ORD-1', $user);
        $order->setTotalPriceCents(12345);
        self::assertSame(1230, $service->calculateEarnedPoints($order));

        $order->setStatus(Order::STATUS_CONFIRMED)->setLoyaltyPointsAwarded(0);
        $service->syncOrderPoints($order);
        self::assertSame(1230, $order->getLoyaltyPointsAwarded());
        self::assertSame(3230, $user->getLoyaltyPointsBalance());

        $service->syncOrderPoints($order);
        self::assertSame(1230, $order->getLoyaltyPointsAwarded());
        self::assertSame(3230, $user->getLoyaltyPointsBalance());

        $order->setStatus(Order::STATUS_CANCELLED);
        $service->syncOrderPoints($order);
        self::assertSame(0, $order->getLoyaltyPointsAwarded());
        self::assertSame(2000, $user->getLoyaltyPointsBalance());

        $service->adjustBalance($user, 800);
        self::assertSame(800, $user->getLoyaltyPointsBalance());

        $voucher = $service->convertPointsToVoucher($user, 500);
        self::assertInstanceOf(Voucher::class, $voucher);
        self::assertSame(300, $user->getLoyaltyPointsBalance());
        self::assertSame(500, $voucher->getDiscountValue());
        self::assertSame($user->getId(), $voucher->getRecipientUserId());
        self::assertSame($user->getEmail(), $voucher->getRecipientEmail());

        try {
            $service->convertPointsToVoucher($user, 0);
            self::fail('Expected exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le montant à convertir doit représenter au moins 1 €.', $exception->getMessage());
        }

        try {
            $service->convertPointsToVoucher($user, 1000);
            self::fail('Expected exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Solde de fidélité insuffisant.', $exception->getMessage());
        }

        $accentedUser = new User('client@example.com', 'Client', 'Éléphant !!!', new \DateTimeImmutable('1990-01-01'), '0102030405', 'other');
        $this->setId($accentedUser, 19);
        $accentedUser->setLoyaltyPointsBalance(100);
        $accentedVoucher = $service->convertPointsToVoucher($accentedUser, 100);
        self::assertMatchesRegularExpression('/^FID-ELEPHANT-[A-F0-9]{6}$/', $accentedVoucher->getCode());
    }

    public function testLoyaltyServiceCustomerSearchHelpers(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn(['users']);
        $query->method('getSingleScalarResult')->willReturn(12);
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::exactly(4))->method('createQueryBuilder')->with('u')->willReturn($queryBuilder);

        $service = new LoyaltyService(
            new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)),
            new DoctrineTransactionManager($this->createMock(EntityManagerInterface::class)),
            new VoucherManager($this->voucherRepository(), new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class))),
            $users,
        );

        self::assertSame(['users'], $service->findCustomers('ada', 500, -5));
        self::assertSame(['users'], $service->findCustomers('', 0, -5));
        self::assertSame(12, $service->countCustomers('ada'));
        self::assertSame(12, $service->countCustomers(''));
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private ?EntityManager $notificationEm = null;

    private function notificationEntityManager(): EntityManager
    {
        if (null !== $this->notificationEm) {
            return $this->notificationEm;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->notificationEm = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($this->notificationEm);
        $schemaTool->createSchema([
            $this->notificationEm->getClassMetadata(User::class),
            $this->notificationEm->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $this->notificationEm;
    }

    private function notificationRepository(): AccountNotificationEventRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->notificationEntityManager());

        return new AccountNotificationEventRepository($registry);
    }

    private function voucherRepository(): VoucherRepository
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata(Voucher::class)]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new VoucherRepository($registry);
    }
}
