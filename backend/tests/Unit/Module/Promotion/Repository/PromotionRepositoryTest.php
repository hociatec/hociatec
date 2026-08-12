<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Promotion\Repository;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class PromotionRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([
            $this->entityManager->getClassMetadata(Promotion::class),
            $this->entityManager->getClassMetadata(User::class),
            $this->entityManager->getClassMetadata(Order::class),
            $this->entityManager->getClassMetadata(OrderItem::class),
        ]);
    }

    public function testFindActiveForDateReturnsOnlyEligiblePromotions(): void
    {
        $active = (new Promotion('Active', 'active', Promotion::TYPE_PERCENT, 10, 'all_users'))
            ->setStartsAt(new \DateTimeImmutable('2026-07-01T00:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-08-01T00:00:00+00:00'));
        $inactive = (new Promotion('Inactive', 'inactive', Promotion::TYPE_PERCENT, 10, 'all_users'))
            ->setIsActive(false)
            ->setStartsAt(new \DateTimeImmutable('2026-07-01T00:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-08-01T00:00:00+00:00'));
        $expired = (new Promotion('Expired', 'expired', Promotion::TYPE_PERCENT, 10, 'all_users'))
            ->setStartsAt(new \DateTimeImmutable('2026-06-01T00:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-06-30T00:00:00+00:00'));

        $this->entityManager->persist($active);
        $this->entityManager->persist($inactive);
        $this->entityManager->persist($expired);
        $this->entityManager->flush();

        $results = $this->repository()->findActiveForDate(new \DateTimeImmutable('2026-07-29T00:00:00+00:00'));

        self::assertCount(1, $results);
        self::assertSame('active', $results[0]->getSlug());
    }

    public function testFindUserOrderStatsReturnsCountAndLatestDate(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $other = new User('grace@example.com', 'Grace', 'Hopper', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $user->setPassword('hashed');
        $other->setPassword('hashed');

        $first = new Order('ORD-1', $user);
        $second = new Order('ORD-2', $user);
        $ignored = new Order('ORD-3', $other);

        $this->setCreatedAt($first, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
        $this->setCreatedAt($second, new \DateTimeImmutable('2026-07-20T10:00:00+00:00'));
        $this->setCreatedAt($ignored, new \DateTimeImmutable('2026-07-25T10:00:00+00:00'));

        foreach ([$user, $other, $first, $second, $ignored] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $stats = $this->repository()->findUserOrderStats($user);

        self::assertSame(2, $stats['ordersCount']);
        self::assertInstanceOf(\DateTimeImmutable::class, $stats['lastOrderAt']);
        self::assertSame('2026-07-20T10:00:00+00:00', $stats['lastOrderAt']?->format(DATE_ATOM));
    }

    public function testFindUserOrderStatsReturnsZeroAndNullWhenUserHasNoOrders(): void
    {
        $user = new User('nobody@example.com', 'No', 'Body', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $user->setPassword('hashed');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $stats = $this->repository()->findUserOrderStats($user);

        self::assertSame(0, $stats['ordersCount']);
        self::assertNull($stats['lastOrderAt']);
    }

    private function repository(): PromotionRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new PromotionRepository($registry);
    }

    private function setCreatedAt(object $entity, \DateTimeImmutable $createdAt): void
    {
        $reflection = new \ReflectionObject($entity);
        foreach (['createdAt', 'updatedAt'] as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                $reflection->getProperty($propertyName)->setValue($entity, $createdAt);
            }
        }
    }
}
