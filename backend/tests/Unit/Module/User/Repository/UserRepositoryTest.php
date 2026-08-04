<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Repository;

use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([
            $this->entityManager->getClassMetadata(User::class),
            $this->entityManager->getClassMetadata(Order::class),
        ]);
    }

    public function testUserRepositoryQueriesAndAggregates(): void
    {
        $ada = $this->user('ada@example.com', 'Ada', 'Lovelace');
        $grace = $this->user('grace@example.com', 'Grace', 'Hopper');
        $admin = $this->user('admin@example.com', 'Admin', 'Root');

        $ada
            ->setIsVerified(true)
            ->setCommunicationPreferences(['news_email'])
            ->setVerificationToken('verify-ada')
            ->setPasswordResetToken('reset-ada')
            ->setAdminTags(['vip', ' enterprise ']);
        $grace
            ->setIsVerified(true)
            ->setCommunicationPreferences(['email'])
            ->setVerificationToken('legacy-verify')
            ->setAdminTags([' prospect ']);
        $admin
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true)
            ->setCommunicationPreferences(['news_email'])
            ->setAdminTags(['staff']);

        $orderA1 = (new Order('ORD-1', $ada))->setTotalPriceCents(1000);
        $orderA2 = (new Order('ORD-2', $ada))->setTotalPriceCents(3000);
        $orderG1 = (new Order('ORD-3', $grace))->setTotalPriceCents(2000);

        $this->setDates($ada, '2026-07-10T10:00:00+00:00');
        $this->setDates($grace, '2026-07-20T10:00:00+00:00');
        $this->setDates($admin, '2026-07-21T10:00:00+00:00');
        $this->setOrderDate($orderA1, '2026-07-15T10:00:00+00:00');
        $this->setOrderDate($orderA2, '2026-07-25T10:00:00+00:00');
        $this->setOrderDate($orderG1, '2026-07-22T10:00:00+00:00');

        foreach ([$ada, $grace, $admin, $orderA1, $orderA2, $orderG1] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $repository = $this->repository();

        self::assertTrue($repository->existsByEmail('ADA@example.com'));
        self::assertFalse($repository->existsByEmail('missing@example.com'));
        self::assertSame($ada->getId(), $repository->findOneByEmailInsensitive('ADA@example.com')?->getId());
        self::assertTrue($repository->existsByEmailExcludingUser('ada@example.com', $grace->getId() ?? 0));
        self::assertFalse($repository->existsByEmailExcludingUser('ada@example.com', $ada->getId() ?? 0));
        self::assertSame($ada->getId(), $repository->findOneByVerificationTokens('verify-ada', 'missing')?->getId());
        self::assertSame($grace->getId(), $repository->findOneByVerificationTokens('missing', 'legacy-verify')?->getId());
        self::assertSame($ada->getId(), $repository->findOneByPasswordResetToken('reset-ada')?->getId());
        self::assertSame([$admin->getId()], array_map(static fn (User $u): ?int => $u->getId(), $repository->findAdmins()));
        self::assertEqualsCanonicalizing(
            [$admin->getId(), $ada->getId()],
            array_map(static fn (User $u): ?int => $u->getId(), $repository->findNewsEmailSubscribers())
        );

        $recent = $repository->findAdminCustomerRows(null, 'recent_order', 10, 0);
        self::assertCount(3, $recent);
        self::assertSame($ada->getEmail(), $recent[0]['email']);
        self::assertSame(2, $recent[0]['ordersCount']);
        self::assertSame(4000, $recent[0]['totalSpentCents']);
        self::assertSame(['vip', 'enterprise'], $recent[0]['adminTags']);

        $highestSpent = $repository->findAdminCustomerRows(null, 'highest_spent', 10, 0);
        self::assertSame($ada->getEmail(), $highestSpent[0]['email']);

        $mostOrders = $repository->findAdminCustomerRows(null, 'most_orders', 10, 0);
        self::assertSame($ada->getEmail(), $mostOrders[0]['email']);

        $newest = $repository->findAdminCustomerRows(null, 'newest_account', 10, 0);
        self::assertContains($admin->getEmail(), array_column($newest, 'email'));

        $byName = $repository->findAdminCustomerRows(null, 'name_asc', 10, 0);
        self::assertSame($grace->getEmail(), $byName[0]['email']);

        $searchRows = $repository->findAdminCustomerRows('ord-2', 'recent_order', 10, 0);
        self::assertCount(1, $searchRows);
        self::assertSame($ada->getEmail(), $searchRows[0]['email']);
        self::assertSame(1, $repository->countAdminCustomerRows('Ada'));
    }

    public function testSaveAndRemovePrepareUnitOfWork(): void
    {
        $repository = $this->repository();
        $user = $this->user('save@example.com', 'Save', 'User');

        $repository->save($user);
        $this->entityManager->flush();
        self::assertNotNull($user->getId());

        $id = $user->getId();
        self::assertNotNull($id);
        $managed = $this->entityManager->find(User::class, $id);
        self::assertInstanceOf(User::class, $managed);

        $repository->remove($managed);
        $this->entityManager->flush();
        self::assertNull($this->entityManager->find(User::class, $id));
    }

    private function repository(): UserRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new UserRepository($registry);
    }

    private function user(string $email, string $firstName, string $lastName): User
    {
        $user = new User($email, $firstName, $lastName, new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setDates(User $user, string $createdAt): void
    {
        $value = new \DateTimeImmutable($createdAt);
        $reflection = new \ReflectionObject($user);
        foreach (['createdAt', 'updatedAt'] as $property) {
            $reflection->getProperty($property)->setValue($user, $value);
        }
    }

    private function setOrderDate(Order $order, string $createdAt): void
    {
        $value = new \DateTimeImmutable($createdAt);
        $reflection = new \ReflectionObject($order);
        foreach (['createdAt', 'updatedAt'] as $property) {
            $reflection->getProperty($property)->setValue($order, $value);
        }
    }
}
