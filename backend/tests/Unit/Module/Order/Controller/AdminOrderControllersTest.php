<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Controller;

use App\Module\Admin\UI\Order\Controller\ListOrdersController;
use App\Module\Admin\UI\Order\Controller\ShowOrderController;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminOrderControllersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testListOrdersControllerBuildsPaginatedPayloadWithIssueFilter(): void
    {
        $entityManager = $this->entityManager();
        [$user, $issueOrder] = $this->persistOrderFixture($entityManager, withIssue: true);
        [, $healthyOrder] = $this->persistOrderFixture($entityManager, withIssue: false, user: $user, number: 'ORD-2026-0002', healthy: true);
        $this->setCreatedAt($issueOrder, '2026-07-21T10:00:00+00:00');
        $this->setCreatedAt($healthyOrder, '2026-07-20T10:00:00+00:00');
        $entityManager->flush();

        $controller = new ListOrdersController(
            $this->orderRepository($entityManager),
            $this->eventRepository($entityManager),
            \App\Tests\Support\OrderFormatterFactory::create(),
        );
        $response = $controller(Request::create('/api/admin/orders?status=confirmed&health=issues&page=1&perPage=1', 'GET'));
        $payload = $this->payload($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, $payload['data']['meta']['total']);
        self::assertSame(1, $payload['data']['meta']['page']);
        self::assertSame(1, $payload['data']['meta']['totalPages']);
        self::assertSame((int) $issueOrder->getId(), $payload['data']['items'][0]['id']);
        self::assertSame('ORD-2026-0001', $payload['data']['items'][0]['number']);
        self::assertSame('confirmed', $payload['data']['items'][0]['status']);
        self::assertTrue($payload['data']['items'][0]['hasIssues']);
        self::assertContains('Échec d’envoi email : SMTP down', $payload['data']['items'][0]['issueReasons']);
        self::assertContains('Facture PDF non générée', $payload['data']['items'][0]['issueReasons']);
    }

    public function testShowOrderControllerHandlesNotFoundAndFormatsPaymentAndEvents(): void
    {
        $entityManager = $this->entityManager();
        [, $order] = $this->persistOrderFixture($entityManager, withIssue: true);
        [, $orderWithoutPayment] = $this->persistOrderFixture($entityManager, withIssue: false, user: $order->getUser(), number: 'ORD-2026-0003', healthy: true);
        $event = new OrderEvent($order, 'post_processing_failed', 'ERP timeout', 9, 'Ops');
        $payment = (new OrderCheckoutSession('tok-show', $order->getUser(), 'cart-show', 22, 'sess-show', 'https://stripe.test/show'))
            ->setStatus(OrderCheckoutSession::STATUS_FAILED)
            ->setStripePaymentStatus('requires_payment_method')
            ->setLastStripeEventType('payment_intent.payment_failed')
            ->setFailureCode('card_declined')
            ->setFailureMessage('Carte refusée')
            ->setExpiresAt(new \DateTimeImmutable('2026-07-31T10:00:00+00:00'))
            ->setOrderId((int) $order->getId());
        $entityManager->persist($event);
        $entityManager->persist($payment);
        $entityManager->flush();

        $controller = new ShowOrderController(
            $this->orderRepository($entityManager),
            $this->eventRepository($entityManager),
            $this->checkoutRepository($entityManager),
            \App\Tests\Support\OrderFormatterFactory::create(),
        );

        $notFound = $controller->__invoke(999999);
        self::assertSame(Response::HTTP_NOT_FOUND, $notFound->getStatusCode());
        self::assertSame('Commande introuvable.', $this->payload($notFound)['message']);

        $response = $controller->__invoke((int) $order->getId());
        $payload = $this->payload($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('ORD-2026-0001', $payload['data']['order']['number']);
        self::assertTrue($payload['data']['order']['hasIssues']);
        self::assertSame('failed', $payload['data']['payment']['status']);
        self::assertSame('Échoué', $payload['data']['payment']['statusLabel']);
        self::assertSame('card_declined', $payload['data']['payment']['failureCode']);
        self::assertSame('Admin', $payload['data']['events'][0]['actor']['name']);
        self::assertSame('SMTP down', $payload['data']['events'][0]['message']);
        self::assertSame('Ops', $payload['data']['events'][1]['actor']['name']);
        self::assertSame('ERP timeout', $payload['data']['events'][1]['message']);
        self::assertFalse($payload['data']['processing']['invoicePdfGenerated']);
        self::assertFalse($payload['data']['processing']['invoiceXmlGenerated']);
        self::assertSame('2026-07-20T12:30:00+00:00', $payload['data']['processing']['orderCreatedEmailSentAt']);

        $withoutPayment = $this->payload($controller->__invoke((int) $orderWithoutPayment->getId()));
        self::assertNull($withoutPayment['data']['payment']);
        self::assertFalse($withoutPayment['data']['order']['hasIssues']);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function persistOrderFixture(EntityManager $entityManager, bool $withIssue, ?User $user = null, string $number = 'ORD-2026-0001', bool $healthy = false): array
    {
        $user ??= new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        if (null === $user->getId()) {
            $user->setPassword('hashed');
            $entityManager->persist($user);
        }

        $order = new Order($number, $user);
        $order->setStatus(Order::STATUS_CONFIRMED)
            ->setSubtotalPriceCents(12000)
            ->setDiscountAmountCents(1000)
            ->setTotalPriceCents(11000)
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('1 rue de Paris')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setOrderCreatedEmailSentAt(new \DateTimeImmutable('2026-07-20T12:30:00+00:00'));
        if ($healthy) {
            $order->setInvoicePdfPath('/tmp/invoice.pdf')
                ->setInvoiceXmlPath('/tmp/invoice.xml');
        }

        $entityManager->persist($order);
        $entityManager->flush();

        if ($withIssue) {
            $entityManager->persist(new OrderEvent($order, 'email_failed', 'SMTP down', 7, 'Admin'));
            $entityManager->flush();
        }

        return [$user, $order];
    }

    private function payload(object $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
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
            $entityManager->getClassMetadata(OrderCheckoutSession::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function orderRepository(EntityManager $entityManager): OrderRepository
    {
        return new OrderRepository($this->registry($entityManager));
    }

    private function eventRepository(EntityManager $entityManager): OrderEventRepository
    {
        return new OrderEventRepository($this->registry($entityManager));
    }

    private function checkoutRepository(EntityManager $entityManager): OrderCheckoutSessionRepository
    {
        return new OrderCheckoutSessionRepository($this->registry($entityManager));
    }

    private function registry(EntityManager $entityManager): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return $registry;
    }

    private function setCreatedAt(Order $order, string $date): void
    {
        $reflection = new \ReflectionObject($order);
        $value = new \DateTimeImmutable($date);
        $reflection->getProperty('createdAt')->setValue($order, $value);
        $reflection->getProperty('updatedAt')->setValue($order, $value);
    }
}
