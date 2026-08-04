<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Operations;

use App\Module\Admin\UI\Operations\Controller\CustomerTimelineController;
use App\Module\Admin\UI\Operations\Controller\EmailLogsController;
use App\Module\Admin\UI\Operations\Controller\OperationsExportController;
use App\Module\Admin\UI\Operations\Controller\OperationsOverviewController;
use App\Module\Admin\Application\Operations\Service\AdminOperationsExporter;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Admin\Application\Operations\Service\CustomerTimelineProvider;
use App\Module\Admin\Application\Operations\Service\OperationsOverviewProvider;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Infrastructure\Repository\StockMovementRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminOperationsControllersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testOperationsControllersExposeOverviewExportsTimelineAndEmailLogs(): void
    {
        $customer = $this->seedOperationsData();
        $formatter = $this->formatter();

        $overview = $this->payload((new OperationsOverviewController(new OperationsOverviewProvider(
            $this->supportRequests(),
            $this->refunds(),
            $this->products(),
            $this->stockMovements(),
            $formatter,
        )))());
        self::assertSame(1, $overview['data']['support']['openCount']);
        self::assertSame(1, $overview['data']['refunds']['pendingCount']);
        self::assertSame(1, $overview['data']['stock']['lowStockCount']);
        self::assertSame('/api/admin/operations/exports/orders.csv', $overview['data']['actions'][0]['href']);

        $emailLogs = $this->payload((new EmailLogsController($formatter))());
        self::assertSame('email_failed', $emailLogs['data']['items'][0]['scenario']);
        self::assertSame('order_created', $emailLogs['data']['items'][1]['scenario']);

        $timeline = new CustomerTimelineController(new CustomerTimelineProvider(
            $this->users(),
            $this->orders(),
            $this->supportRequests(),
            $this->quotes(),
            $formatter,
        ));
        self::assertSame(404, $timeline(999)->getStatusCode());
        $timelinePayload = $this->payload($timeline((int) $customer->getId()));
        self::assertSame(['order', 'support', 'quote'], array_column($timelinePayload['data']['items'], 'type'));

        $export = new OperationsExportController(new AdminOperationsExporter(
            $this->orders(),
            $this->users(),
            $this->products(),
            $this->quotes(),
            $this->refunds(),
            $this->supportRequests(),
        ), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        $ordersCsv = $this->streamedContent($export('orders'));
        self::assertStringContainsString('id;numero;client;email;statut;total_centimes;date', $ordersCsv);
        self::assertStringContainsString('ORD-OPS-1', $ordersCsv);

        self::assertStringContainsString('operations@example.test', $this->streamedContent($export('customers')));
        self::assertStringContainsString('PH-OPS', $this->streamedContent($export('products')));
        self::assertStringContainsString('QUO-OPS-1', $this->streamedContent($export('quotes')));
        self::assertStringContainsString('duplicate', $this->streamedContent($export('refunds')));
        self::assertStringContainsString('delivery', $this->streamedContent($export('support')));

        $unknownCsv = $this->streamedContent($export('unknown'));
        self::assertStringContainsString("Erreur\n\"Export inconnu\"\n", str_replace("\r\n", "\n", $unknownCsv));
    }

    private function seedOperationsData(): User
    {
        $customer = new User('operations@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $customer->setPassword('hashed');
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-OPS', 'Desc', 100000, 2, $category);
        $product->setLowStockThreshold(3);

        $order = (new Order('ORD-OPS-1', $customer))
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setTotalPriceCents(200000)
            ->setBillingEmail('billing-ops@example.test')
            ->setOrderCreatedEmailSentAt(new \DateTimeImmutable('2026-07-22 10:00:00'));
        $order->addItem(new OrderItem('Phone', 'PH-OPS', 100000, 2));

        $support = (new SupportRequest($customer, 'Suivi'))
            ->setOrder($order)
            ->setStatus(SupportRequest::STATUS_NEW)
            ->setReason('delivery')
            ->setMessage('Ou en est la livraison ?');

        $refund = (new RefundRequest($order, 1200, $customer))
            ->setStatus(RefundRequest::STATUS_REQUESTED)
            ->setReason('duplicate');
        $movement = new StockMovement($product, -1, 3, 2, 'Correction stock', $customer);
        $quote = (new Quote('QUO-OPS-1'))
            ->setCustomerName($customer->getFullName())
            ->setCustomerEmail($customer->getEmail())
            ->setStatus(Quote::STATUS_SENT);

        foreach ([$customer, $category, $product, $order, $support, $refund, $movement, $quote] as $entity) {
            $this->entityManager()->persist($entity);
        }
        $this->entityManager()->flush();

        $event = new OrderEvent($order, 'email_failed', 'SMTP indisponible', null, null);
        $this->entityManager()->persist($event);
        $this->entityManager()->flush();

        return $customer;
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Brand::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(StockMovement::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderItem::class),
            $entityManager->getClassMetadata(OrderEvent::class),
            $entityManager->getClassMetadata(RefundRequest::class),
            $entityManager->getClassMetadata(SupportRequest::class),
            $entityManager->getClassMetadata(Quote::class),
            $entityManager->getClassMetadata(QuoteItem::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function formatter(): AdminOperationsFormatter
    {
        return new AdminOperationsFormatter($this->orders(), $this->orderEvents());
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    private function orders(): OrderRepository
    {
        return new OrderRepository($this->registry());
    }

    private function orderEvents(): OrderEventRepository
    {
        return new OrderEventRepository($this->registry());
    }

    private function users(): UserRepository
    {
        return new UserRepository($this->registry());
    }

    private function products(): ProductRepository
    {
        return new ProductRepository($this->registry());
    }

    private function stockMovements(): StockMovementRepository
    {
        return new StockMovementRepository($this->registry());
    }

    private function refunds(): RefundRequestRepository
    {
        return new RefundRequestRepository($this->registry());
    }

    private function supportRequests(): SupportRequestRepository
    {
        return new SupportRequestRepository($this->registry());
    }

    private function quotes(): QuoteRepository
    {
        return new QuoteRepository($this->registry());
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function streamedContent(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
