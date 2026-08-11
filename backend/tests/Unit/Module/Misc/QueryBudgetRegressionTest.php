<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Order\Application\Workflow\CustomerOrderPortalService;
use App\Module\Order\Application\Workflow\OrderWorkflowService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Application\Workflow\CustomerTradeInPortalService;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\Training\Application\Calculator\TrainingAvailabilityCalculator;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Projection\TrainingMetadataFormatter;
use App\Module\Training\Application\Workflow\CustomerTrainingPortalService;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\User\Application\Workflow\CustomerAddressBookService;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use App\Tests\Support\OrderFormatterFactory;
use App\Shared\Application\UnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class QueryBudgetRegressionTest extends TestCase
{
    public function testCustomerOrderListingStaysWithinThreeSqlQueries(): void
    {
        [$entityManager, $logger] = $this->entityManagerWithLogger([
            User::class,
            Order::class,
            OrderItem::class,
            \App\Module\Catalog\Domain\Entity\Category::class,
            \App\Module\Catalog\Domain\Entity\Product::class,
            \App\Module\Comment\Domain\Entity\ProductComment::class,
            \App\Module\Rating\Domain\Entity\ProductRating::class,
        ]);

        $user = $this->persistOrderFixtures($entityManager);
        $portal = new CustomerOrderPortalService(
            new OrderRepository($this->registry($entityManager)),
            new ProductRatingRepository($this->registry($entityManager)),
            new OrderAccessPolicy(),
            OrderFormatterFactory::create(),
            new OrderWorkflowService(new class implements UnitOfWork {
                public function persist(object $entity): void
                {
                }

                public function remove(object $entity): void
                {
                }

                public function flush(): void
                {
                }
            }),
        );

        $logger->queries = [];
        $result = $portal->listForUser($user, 10, 0);

        self::assertCount(2, $result['items']);
        self::assertSame(2, $result['total']);
        self::assertLessThanOrEqual(3, count($logger->queries), 'Customer order listing exceeded the SQL query budget.');
    }

    public function testCustomerTradeInListingStaysWithinTwoSqlQueries(): void
    {
        [$entityManager, $logger] = $this->entityManagerWithLogger([
            User::class,
            TradeInRequest::class,
        ]);

        $user = $this->persistTradeInFixtures($entityManager);
        $portal = new CustomerTradeInPortalService(
            new TradeInRequestRepository($this->registry($entityManager)),
            new TradeInFormatter(),
            new TradeInAccessPolicy(),
            $this->createMock(\App\Module\TradeIn\Application\Workflow\TradeInRequestWorkflow::class),
        );

        $logger->queries = [];
        $result = $portal->listForUser($user, 10, 0);

        self::assertCount(2, $result['items']);
        self::assertSame(2, $result['total']);
        self::assertLessThanOrEqual(2, count($logger->queries), 'Customer trade-in listing exceeded the SQL query budget.');
    }

    public function testCustomerAddressListingStaysWithinTwoSqlQueries(): void
    {
        [$entityManager, $logger] = $this->entityManagerWithLogger([
            User::class,
            ShippingAddress::class,
        ]);

        $user = $this->persistAddressFixtures($entityManager);
        $service = new CustomerAddressBookService(
            new ShippingAddressRepository($this->registry($entityManager)),
            new ShippingAddressFormatter(),
        );

        $logger->queries = [];
        $result = $service->listForUser($user, 10, 0);

        self::assertCount(2, $result['items']);
        self::assertSame(2, $result['total']);
        self::assertLessThanOrEqual(2, count($logger->queries), 'Customer address listing exceeded the SQL query budget.');
    }

    public function testCustomerTrainingListingStaysWithinFiveSqlQueries(): void
    {
        [$entityManager, $logger] = $this->entityManagerWithLogger([
            User::class,
            Training::class,
            TrainingCategory::class,
            TrainingRoadmapItem::class,
            TrainingSession::class,
            TrainingEnrollment::class,
        ]);

        $user = $this->persistTrainingFixtures($entityManager);
        $service = new CustomerTrainingPortalService(
            new TrainingEnrollmentRepository($this->registry($entityManager)),
            new TrainingFormatter(
                new TrainingEnrollmentRepository($this->registry($entityManager)),
                new TrainingMetadataFormatter($this->createMock(TrainingCategoryRepository::class)),
                new TrainingAvailabilityCalculator(),
            ),
        );

        $logger->queries = [];
        $result = $service->listEnrollmentsForUser($user, 10, 0);

        self::assertCount(2, $result['items']);
        self::assertSame(2, $result['total']);
        self::assertLessThanOrEqual(5, count($logger->queries), 'Customer training listing exceeded the SQL query budget.');
    }

    /**
     * @param list<class-string> $metadataClasses
     *
     * @return array{0: EntityManager, 1: DebugStack}
     */
    private function entityManagerWithLogger(array $metadataClasses): array
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $logger = new DebugStack();
        $config->setSQLLogger($logger);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);

        $tool = new SchemaTool($entityManager);
        $tool->createSchema(array_map($entityManager->getClassMetadata(...), $metadataClasses));

        return [$entityManager, $logger];
    }

    private function persistOrderFixtures(EntityManager $entityManager): User
    {
        $category = new \App\Module\Catalog\Domain\Entity\Category('Phones', 'phones');
        $firstProduct = new \App\Module\Catalog\Domain\Entity\Product('Phone 1', 'phone-1', 'PH-1', 'Phone', 10000, 10, $category);
        $secondProduct = new \App\Module\Catalog\Domain\Entity\Product('Phone 2', 'phone-2', 'PH-2', 'Phone', 12000, 10, $category);
        $user = new User('orders@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        $firstOrder = (new Order('ORD-QUERY-1', $user))
            ->setStatus(Order::STATUS_DELIVERED)
            ->setSubtotalPriceCents(10000)
            ->setDiscountAmountCents(0)
            ->setTotalPriceCents(10000)
            ->setShippingName('Ada')
            ->setShippingAddress('1 rue A')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris');
        $firstOrder->addItem(
            (new OrderItem('Phone 1', 'PH-1', 10000, 1))
                ->setProduct($firstProduct)
                ->replaceLineTotals(10000, 0, 10000),
        );

        $secondOrder = (new Order('ORD-QUERY-2', $user))
            ->setStatus(Order::STATUS_DELIVERED)
            ->setSubtotalPriceCents(12000)
            ->setDiscountAmountCents(0)
            ->setTotalPriceCents(12000)
            ->setShippingName('Ada')
            ->setShippingAddress('2 rue B')
            ->setShippingPostalCode('75002')
            ->setShippingCity('Paris');
        $secondOrder->addItem(
            (new OrderItem('Phone 2', 'PH-2', 12000, 1))
                ->setProduct($secondProduct)
                ->replaceLineTotals(12000, 0, 12000),
        );

        $entityManager->persist($category);
        $entityManager->persist($firstProduct);
        $entityManager->persist($secondProduct);
        $entityManager->persist($user);
        $entityManager->persist($firstOrder);
        $entityManager->persist($secondOrder);
        $entityManager->flush();

        return $user;
    }

    private function persistTradeInFixtures(EntityManager $entityManager): User
    {
        $user = new User('tradein@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $entityManager->persist($user);
        $entityManager->flush();

        foreach ([1, 2] as $index) {
            $entityManager->persist(new TradeInRequest(
                'TR-QUERY-'.$index,
                $user,
                new TradeInApplicant('Ada', 'Lovelace', 'tradein@example.com', '0102030405'),
                new TradeInProductSnapshot(
                    new \App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity(
                        'smartphone',
                        'Phone '.$index,
                        new \App\Module\TradeIn\Domain\ValueObject\TradeInTechnicalIdentity('Brand', 'Model', 'SN-'.$index),
                    ),
                    new \App\Module\TradeIn\Domain\ValueObject\TradeInPurchase(100000, 2025),
                    new \App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition('bon', true, true, true, 'Etat correct'),
                ),
                new TradeInEstimate(10000, 15000, null, null),
                new \DateTimeImmutable('2026-08-11T10:00:00+00:00'),
            ));
        }

        $entityManager->flush();

        return $user;
    }

    private function persistAddressFixtures(EntityManager $entityManager): User
    {
        $user = new User('address@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $entityManager->persist($user);
        $entityManager->flush();

        $entityManager->persist(
            (new ShippingAddress($user, 'Ada Lovelace', '1 rue A', '75001', 'Paris'))
                ->setIsDefault(true)
                ->setCompany('Hociatec'),
        );
        $entityManager->persist(
            (new ShippingAddress($user, 'Ada Lovelace', '2 rue B', '75002', 'Paris'))
                ->setPurchaseOrderNumber('PO-42'),
        );
        $entityManager->flush();

        return $user;
    }

    private function persistTrainingFixtures(EntityManager $entityManager): User
    {
        $user = new User('training@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $category = new TrainingCategory('Web', 'web');
        $training = (new Training('SEO', 'seo', 60, 10000))
            ->setCategory('web')
            ->setIsActive(true);
        $training->addRoadmapItem(new TrainingRoadmapItem(1, 'Intro'));
        $firstSession = new TrainingSession($training, 'remote', new \DateTimeImmutable('2026-08-20T08:00:00+00:00'), new \DateTimeImmutable('2026-08-20T18:00:00+00:00'), 5);
        $secondSession = new TrainingSession($training, 'onsite', new \DateTimeImmutable('2026-08-21T08:00:00+00:00'), new \DateTimeImmutable('2026-08-21T18:00:00+00:00'), 5);

        $entityManager->persist($user);
        $entityManager->persist($category);
        $entityManager->persist($training);
        $entityManager->persist($firstSession);
        $entityManager->persist($secondSession);
        $entityManager->flush();

        $entityManager->persist(new TrainingEnrollment($firstSession, $user, 10000));
        $entityManager->persist(new TrainingEnrollment($secondSession, $user, 10000));
        $entityManager->flush();

        return $user;
    }

    private function registry(EntityManager $entityManager): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return $registry;
    }
}
