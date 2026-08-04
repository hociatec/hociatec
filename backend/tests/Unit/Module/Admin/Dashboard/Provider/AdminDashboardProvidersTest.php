<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Dashboard\Provider;

use App\Module\Admin\UI\Dashboard\Controller\GetDashboardController;
use App\Module\Admin\Application\Dashboard\Provider\DashboardActivityProvider;
use App\Module\Admin\Application\Dashboard\Provider\DashboardCustomersProvider;
use App\Module\Admin\Application\Dashboard\Provider\DashboardDataProvider;
use App\Module\Admin\Application\Dashboard\Provider\DashboardMetricsProvider;
use App\Module\Admin\Application\Dashboard\Provider\DashboardNotificationsProvider;
use App\Module\Admin\Application\Dashboard\Provider\DashboardPaymentsProvider;
use App\Module\Admin\Application\Payment\Service\AdminPaymentFormatter;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Service\GroupedLowStockCounter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\Quote\Application\Service\QuoteCalculator;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Infrastructure\Repository\SupportRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class AdminDashboardProvidersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testMetricsProviderAggregatesRepositoryCountsAndSummaries(): void
    {
        [$user, $order] = $this->seedOperationalRows();
        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::exactly(3))->method('getSummaryBetween')->willReturn(['total' => 1000]);
        $orders->expects(self::once())->method('getStatusCounts')->willReturn(['pending' => 2]);
        $orders->expects(self::once())->method('countWithOperationalIssues')->willReturn(3);
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('count')->with([])->willReturn(11);
        $this->entityManager()->persist(new SupportRequest($user, 'Support'));
        $this->entityManager()->persist(new RefundRequest($order, 500, $user));
        $this->entityManager()->flush();
        $lowStock = new GroupedLowStockCounter($this->productRepository());

        $payload = (new DashboardMetricsProvider($orders, $users, $this->supportRequests(), $this->refunds(), $lowStock))->provide();

        self::assertSame(['total' => 1000], $payload['today']);
        self::assertSame(['pending' => 2], $payload['statusCounts']);
        self::assertSame(3, $payload['issuesCount']);
        self::assertSame(1, $payload['lowStockCount']);
        self::assertSame(11, $payload['customersCount']);
        self::assertSame(1, $payload['supportOpenCount']);
        self::assertSame(1, $payload['refundsPendingCount']);
    }

    public function testActivityPaymentsNotificationsAndDataProviderComposeDashboardPayload(): void
    {
        [$user, $order] = $this->seedOperationalRows();
        $event = new OrderEvent($order, 'email_failed', 'SMTP down', 7, 'Admin');
        $ignoredEvent = new OrderEvent($order, 'ignored', 'Skip', null, null);
        $this->entityManager()->persist($event);
        $this->entityManager()->persist($ignoredEvent);
        $this->entityManager()->flush();

        $orders = $this->createMock(OrderRepository::class);
        $orders->method('findRecentForAdmin')->with(6)->willReturn([$order]);
        $orders->method('findPendingPaymentForAdmin')->with(8)->willReturn([$order]);
        $events = $this->orderEvents();
        $activity = new DashboardActivityProvider($orders, $events);

        $payment = (new OrderCheckoutSession('pay-token', $user, 'cart-token', 44, 'stripe-session', 'https://checkout.test'))
            ->setTotalPriceCents(12000)
            ->markPaid('pi_dash', 'paid', 'checkout.session.completed');
        $this->entityManager()->persist($payment);
        $this->entityManager()->flush();
        $payments = new DashboardPaymentsProvider($this->checkoutSessions(), new AdminPaymentFormatter());

        $quote = (new Quote('QUO-DASH-1'))->setStatus(Quote::STATUS_ACCEPTED)->setCustomerEmail('quote@example.test');
        $this->setId($quote, 61);
        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->method('findAcceptedWaitingForConversion')->with(8)->willReturn([$quote]);
        $quotes->method('findRecentByStatuses')->with([Quote::STATUS_REFUSED], 4)->willReturn([]);
        $quotes->method('findRecentlyEmailed')->with(4)->willReturn([]);
        $notifications = new DashboardNotificationsProvider($quotes, new QuoteCalculator(), $orders, $events);

        $customers = $this->createMock(UserRepository::class);
        $customers->method('findAdminCustomerRows')->with(null, 'highest_spent', 5)->willReturn([['email' => 'top@example.test']]);
        $data = new DashboardDataProvider(
            $this->metricsProvider(),
            $notifications,
            $activity,
            new DashboardCustomersProvider($customers),
            $payments,
        );

        $payload = $data->provide();
        self::assertStringStartsWith('ORD-DASH-', $payload['recentOrders'][0]['number']);
        self::assertSame('email_failed', $payload['recentEvents'][0]['type']);
        self::assertSame('quote_accepted', $payload['notifications'][0]['type']);
        self::assertSame('order_pending_payment', $payload['notifications'][1]['type']);
        self::assertSame('email_failed', $payload['notifications'][2]['type']);
        self::assertSame('top@example.test', $payload['topCustomers'][0]['email']);
        self::assertSame(1, $payload['payments']['statusCounts'][OrderCheckoutSession::STATUS_PAID]);

        $responsePayload = json_decode((string) (new GetDashboardController($data))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('metrics', $responsePayload['data']);
    }

    private function metricsProvider(): DashboardMetricsProvider
    {
        [$user, $order] = $this->seedOperationalRows();
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('getSummaryBetween')->willReturn([]);
        $orders->method('getStatusCounts')->willReturn([]);
        $orders->method('countWithOperationalIssues')->willReturn(0);
        $users = $this->createMock(UserRepository::class);
        $users->method('count')->willReturn(0);
        $lowStock = new GroupedLowStockCounter($this->productRepository([]));
        $resolvedSupport = (new SupportRequest($user, 'Resolved'))->setStatus(SupportRequest::STATUS_RESOLVED);
        $approvedRefund = (new RefundRequest($order, 500, $user))->setStatus(RefundRequest::STATUS_APPROVED);
        $this->entityManager()->persist($resolvedSupport);
        $this->entityManager()->persist($approvedRefund);
        $this->entityManager()->flush();

        return new DashboardMetricsProvider($orders, $users, $this->supportRequests(), $this->refunds(), $lowStock);
    }

    /** @return array{User,Order} */
    private function seedOperationalRows(): array
    {
        static $sequence = 0;
        ++$sequence;
        $user = new User(sprintf('ada-%d@example.test', $sequence), 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $order = new Order(sprintf('ORD-DASH-%d', $sequence), $user);
        $order->setTotalPriceCents(12000);
        $this->entityManager()->persist($user);
        $this->entityManager()->persist($order);
        $this->entityManager()->flush();

        return [$user, $order];
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderCheckoutSession::class),
            $entityManager->getClassMetadata(OrderEvent::class),
            $entityManager->getClassMetadata(SupportRequest::class),
            $entityManager->getClassMetadata(RefundRequest::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    private function supportRequests(): SupportRequestRepository
    {
        return new SupportRequestRepository($this->registry());
    }

    private function refunds(): RefundRequestRepository
    {
        return new RefundRequestRepository($this->registry());
    }

    private function orderEvents(): OrderEventRepository
    {
        return new OrderEventRepository($this->registry());
    }

    private function checkoutSessions(): OrderCheckoutSessionRepository
    {
        return new OrderCheckoutSessionRepository($this->registry());
    }

    /** @param list<Product>|null $products */
    private function productRepository(?array $products = null): ProductRepository
    {
        $products ??= [
            (new Product('Low stock', 'low-stock', 'LOW-1', 'Desc', 1000, 2, new Category('Cat', 'cat')))->setIsPublished(true),
            (new Product('Hidden stock', 'hidden-stock', 'HID-1', 'Desc', 1000, 1, new Category('Cat', 'cat')))->setIsPublished(false),
        ];
        $repository = $this->createMock(ProductRepository::class);
        $repository->method('findAllForAdmin')->willReturn($products);

        return $repository;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
