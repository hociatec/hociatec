<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Repository;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderEvent;
use App\Module\Order\Repository\OrderEventRepository;
use App\Module\User\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class OrderEventRepositoryTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testFindByOrderAndIssueGroupingCoverRepositoryMethods(): void
    {
        $entityManager = $this->entityManager();
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $orderA = new Order('ORD-A', $user);
        $orderB = new Order('ORD-B', $user);
        foreach ([$user, $orderA, $orderB] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $event1 = new OrderEvent($orderA, 'email_failed', 'SMTP', null, null);
        $event2 = new OrderEvent($orderA, 'note', 'Internal note', null, null);
        $event3 = new OrderEvent($orderB, 'invoice_generation_failed', 'PDF', null, null);
        $event4 = new OrderEvent($orderB, 'post_processing_failed', 'ERP', null, null);
        foreach ([$event1, $event2, $event3, $event4] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $repository = $this->repository($entityManager);

        self::assertCount(2, $repository->findByOrder($orderA));
        self::assertSame(
            [$event1->getId(), $event2->getId()],
            array_map(static fn (OrderEvent $event): ?int => $event->getId(), $repository->findByOrder($orderA, 'ASC')),
        );

        self::assertSame([], $repository->findIssueEventsGroupedByOrders([]));

        $grouped = $repository->findIssueEventsGroupedByOrders([$orderA, $orderB]);
        self::assertSame([$event1->getId()], array_map(static fn (OrderEvent $event): ?int => $event->getId(), $grouped[(int) $orderA->getId()]));
        self::assertSame(
            [$event3->getId(), $event4->getId()],
            array_map(static fn (OrderEvent $event): ?int => $event->getId(), $grouped[(int) $orderB->getId()]),
        );
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderEvent::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function repository(EntityManager $entityManager): OrderEventRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new OrderEventRepository($registry);
    }
}
