<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Operations\Service;

use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class AdminOperationsFormatterTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testFormatterCoversSupportRefundStockFulfillmentAndEmailLogs(): void
    {
        $entityManager = $this->entityManager();
        [$formatter, $user, $product, $order, $support, $refund, $movement, $event] = $this->seedAndBuildFormatter($entityManager);

        $supportPayload = $formatter->supportRequest($support);
        self::assertSame('Résolu', $supportPayload['statusLabel']);
        self::assertSame('delivery', $supportPayload['reason']);
        self::assertSame('Suivi', $supportPayload['subject']);
        self::assertSame('Où en est la livraison ?', $supportPayload['message']);
        self::assertSame('traité', $supportPayload['internalNotes']);
        self::assertSame($user->getId(), $supportPayload['customer']['id']);
        self::assertSame($order->getId(), $supportPayload['order']['id']);
        self::assertNotNull($supportPayload['resolvedAt']);

        $refundPayload = $formatter->refund($refund);
        self::assertSame($order->getId(), $refundPayload['order']['id']);
        self::assertSame(1200, $refundPayload['amountCents']);
        self::assertSame('EUR', $refundPayload['currencyCode']);
        self::assertSame('approved', $refundPayload['status']);
        self::assertSame('duplicate', $refundPayload['reason']);
        self::assertSame('remboursé', $refundPayload['internalNotes']);
        self::assertSame('re_123', $refundPayload['stripeRefundId']);

        $movementPayload = $formatter->stockMovement($movement);
        self::assertSame($product->getId(), $movementPayload['product']['id']);
        self::assertSame(-3, $movementPayload['delta']);
        self::assertSame('Préparation commande', $movementPayload['reason']);
        self::assertSame('lot A', $movementPayload['note']);
        self::assertSame($user->getFullName(), $movementPayload['actor']);

        $lowStockPayload = $formatter->lowStockProduct($product);
        self::assertSame('Phones', $lowStockPayload['category']);
        self::assertSame(2, $lowStockPayload['stock']);
        self::assertSame(3, $lowStockPayload['lowStockThreshold']);

        $fulfillmentPayload = $formatter->fulfillmentOrder($order);
        self::assertSame('Confirmée', $fulfillmentPayload['statusLabel']);
        self::assertSame('En transit', $fulfillmentPayload['delivery']['statusLabel']);
        self::assertSame('Ada Lovelace', $fulfillmentPayload['customer']['name']);
        self::assertSame('Colissimo', $fulfillmentPayload['delivery']['carrier']);
        self::assertSame([['name' => 'Phone', 'sku' => 'PH-1', 'quantity' => 2]], $fulfillmentPayload['items']);

        $emailLogs = $formatter->emailLogs();
        self::assertCount(5, $emailLogs);
        self::assertSame('email_failed', $emailLogs[0]['scenario']);
        self::assertSame('Échec', $emailLogs[0]['statusLabel']);
        self::assertSame('status_delivered', $emailLogs[1]['scenario'] ?? null);
        self::assertSame('Commande livrée', $emailLogs[1]['scenarioLabel']);
        self::assertSame('billing@example.test', $emailLogs[2]['recipient']);
        self::assertContains($emailLogs[2]['scenario'], ['status_confirmed', 'invoice', 'order_created']);
        self::assertSame('Confirmation de commande', $this->findScenario($emailLogs, 'order_created')['scenarioLabel']);
        self::assertSame('Facture envoyée', $this->findScenario($emailLogs, 'invoice')['scenarioLabel']);

        self::assertSame('Nouveau', $formatter->supportStatusLabel(SupportRequest::STATUS_NEW));
        self::assertSame('En cours', $formatter->supportStatusLabel(SupportRequest::STATUS_IN_PROGRESS));
        self::assertSame('En attente client', $formatter->supportStatusLabel(SupportRequest::STATUS_WAITING_CUSTOMER));
        self::assertSame('Refusé', $formatter->supportStatusLabel(SupportRequest::STATUS_REFUSED));
        self::assertSame('custom', $formatter->supportStatusLabel('custom'));
        self::assertSame('Commande annulée', $this->invokeEmailScenarioLabel($formatter, 'order_status_cancelled'));
        self::assertSame('Bon de réduction client', $this->invokeEmailScenarioLabel($formatter, 'customer_voucher_offer'));
        self::assertSame('Email failed custom', $this->invokeEmailScenarioLabel($formatter, 'email_failed_custom'));
    }

    /**
     * @return array{AdminOperationsFormatter,User,Product,Order,SupportRequest,RefundRequest,StockMovement,OrderEvent}
     */
    private function seedAndBuildFormatter(EntityManager $entityManager): array
    {
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 100000, 2, $category);
        $product->setLowStockThreshold(3);
        $order = new Order('ORD-2026-0001', $user);
        $order
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setTotalPriceCents(200000)
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('10 rue Exemple')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setDeliveryStatus(Order::DELIVERY_STATUS_IN_TRANSIT)
            ->setDeliveryCarrier('Colissimo')
            ->setDeliveryTrackingNumber('TRACK-1')
            ->setDeliveryTrackingUrl('https://track.example.test/1')
            ->setBillingEmail('billing@example.test')
            ->setOrderCreatedEmailSentAt(new \DateTimeImmutable('2026-07-20 10:00:00'))
            ->setInvoiceEmailSentAt(new \DateTimeImmutable('2026-07-21 10:00:00'))
            ->setStatusConfirmedEmailSentAt(new \DateTimeImmutable('2026-07-22 10:00:00'))
            ->setStatusDeliveredEmailSentAt(new \DateTimeImmutable('2026-07-24 12:00:00'));
        $item = new OrderItem('Phone', 'PH-1', 100000, 2);
        $order->addItem($item);

        $support = new SupportRequest($user, 'Suivi');
        $support
            ->setOrderId($order->getId(), $order->getNumber())
            ->setStatus(SupportRequest::STATUS_RESOLVED)
            ->setReason('delivery')
            ->setMessage('Où en est la livraison ?')
            ->setInternalNotes('traité');

        $refund = new RefundRequest($order, 1200, $user);
        $refund
            ->setPaymentId(99)
            ->setStatus(RefundRequest::STATUS_APPROVED)
            ->setReason('duplicate')
            ->setInternalNotes('remboursé')
            ->setStripeRefundId('re_123');

        $movement = new StockMovement($product, -3, 5, 2, 'Préparation commande', $user);
        $movement->setNote('lot A');

        foreach ([$user, $category, $product, $order, $item, $support, $refund, $movement] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $support->setOrderId($order->getId(), $order->getNumber());

        $event = new OrderEvent($order, 'email_failed', 'SMTP indisponible', null, null);
        $entityManager->persist($event);
        $entityManager->flush();

        $formatter = new AdminOperationsFormatter(
            new \App\Module\Admin\Application\Operations\Projection\AdminOperationsEmailLogFormatter(
                $this->repository(OrderRepository::class, $entityManager),
                $this->repository(OrderEventRepository::class, $entityManager),
            ),
            \App\Tests\Support\OrderFormatterFactory::create(),
        );

        return [$formatter, $user, $product, $order, $support, $refund, $movement, $event];
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
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderItem::class),
            $entityManager->getClassMetadata(OrderEvent::class),
            $entityManager->getClassMetadata(RefundRequest::class),
            $entityManager->getClassMetadata(SupportRequest::class),
            $entityManager->getClassMetadata(StockMovement::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     *
     * @return T
     */
    private function repository(string $repositoryClass, EntityManager $entityManager): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new $repositoryClass($registry);
    }

    /** @param list<array<string, mixed>> $logs */
    private function findScenario(array $logs, string $scenario): array
    {
        foreach ($logs as $log) {
            if (($log['scenario'] ?? null) === $scenario) {
                return $log;
            }
        }

        self::fail('Scenario not found: '.$scenario);
    }

    private function invokeEmailScenarioLabel(AdminOperationsFormatter $formatter, string $scenario): string
    {
        $reflection = new \ReflectionObject($formatter);
        $method = $reflection->getMethod('emailScenarioLabel');
        $method->setAccessible(true);

        return $method->invoke($formatter, $scenario);
    }
}
