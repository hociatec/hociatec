<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Order;

use App\Module\Admin\UI\Order\Controller\ListOrderMetadataController;
use App\Module\Admin\UI\Order\Controller\UpdateOrderDeliveryController;
use App\Module\Admin\UI\Order\Controller\UpdateOrderStatusController;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Writer\OrderDeliveryUpdater;
use App\Module\Order\Application\Writer\OrderStatusUpdater;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Infrastructure\Persistence\OrderEventPersistence;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

final class AdminOrderActionControllersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testOrderMetadataStatusAndDeliveryControllers(): void
    {
        $actor = $this->persistUser('admin-order@example.test');
        $order = $this->persistOrder($actor);

        $metadata = $this->payload((new ListOrderMetadataController(new \App\Module\Order\Application\Projection\OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow())))());
        self::assertSame(Order::STATUS_PENDING, $metadata['data']['statuses'][0]['value']);

        $status = new UpdateOrderStatusController($this->orders(), $this->statusUpdater(true), $this->validator(), new \App\Module\Order\Application\Projection\OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()));
        $status->setContainer($this->container($actor));
        self::assertSame(404, $status(999, $this->jsonRequest(['status' => Order::STATUS_CONFIRMED], 'PATCH'))->getStatusCode());
        self::assertSame(400, $status((int) $order->getId(), $this->jsonRequest(['status' => 'unknown'], 'PATCH'))->getStatusCode());

        $conflict = new UpdateOrderStatusController($this->orders(), $this->statusUpdater(false), $this->validator(), new \App\Module\Order\Application\Projection\OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()));
        $conflict->setContainer($this->container($actor));
        self::assertSame(409, $conflict((int) $order->getId(), $this->jsonRequest(['status' => Order::STATUS_CONFIRMED], 'PATCH'))->getStatusCode());

        $updatedStatus = $this->payload($status((int) $order->getId(), $this->jsonRequest(['status' => Order::STATUS_CONFIRMED], 'PATCH')));
        self::assertSame(Order::STATUS_CONFIRMED, $updatedStatus['data']['order']['status']);

        $delivery = new UpdateOrderDeliveryController($this->orders(), $this->deliveryUpdater(), $this->validator(), new \App\Module\Order\Application\Projection\OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()));
        $delivery->setContainer($this->container($actor));
        self::assertSame(404, $delivery(999, $this->jsonRequest(['status' => Order::DELIVERY_STATUS_SHIPPED], 'PATCH'))->getStatusCode());
        self::assertSame(400, $delivery((int) $order->getId(), $this->jsonRequest(['status' => 'lost'], 'PATCH'))->getStatusCode());

        $delivered = $this->payload($delivery((int) $order->getId(), $this->jsonRequest([
            'status' => Order::DELIVERY_STATUS_SHIPPED,
            'carrier' => 'Colissimo',
            'trackingNumber' => 'TRACK-OPS',
            'trackingUrl' => 'https://track.example.test/TRACK-OPS',
            'estimatedAt' => '2026-08-05',
        ], 'PATCH')));
        self::assertSame(Order::DELIVERY_STATUS_SHIPPED, $delivered['data']['order']['delivery']['status']);
        self::assertSame('Colissimo', $delivered['data']['order']['delivery']['carrier']);
    }

    private function statusUpdater(bool $canTransition): OrderStatusUpdater
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn($canTransition);
        $workflow->method('apply')->willReturnCallback(static function (Order $order, string $transition): Marking {
            if ('confirm' === $transition) {
                $order->setStatus(Order::STATUS_CONFIRMED);
            }

            return new Marking([$order->getStatus() => 1]);
        });
        $bus = $this->createMock(AsyncMessageDispatcher::class);
        $bus->method('dispatch')->willReturnCallback(static fn (object $message): null => null);

        return new OrderStatusUpdater(new DoctrineUnitOfWork($this->entityManager()), $workflow, $bus, $this->eventLogger(), new \App\Module\Order\Application\Projection\OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow());
    }

    private function deliveryUpdater(): OrderDeliveryUpdater
    {
        return new OrderDeliveryUpdater(new DoctrineUnitOfWork($this->entityManager()), $this->eventLogger());
    }

    private function eventLogger(): OrderEventLogger
    {
        return new OrderEventLogger(new OrderEventPersistence($this->entityManager()));
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    private function persistUser(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function persistOrder(User $user): Order
    {
        $order = (new Order('ORD-ACTION-1', $user))
            ->setStatus(Order::STATUS_PENDING)
            ->setTotalPriceCents(100000)
            ->setShippingName($user->getFullName())
            ->setShippingAddress('10 rue Exemple')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris');
        $order->addItem(new OrderItem('Phone', 'PH-ACTION', 100000, 1));
        $this->entityManager()->persist($order);
        $this->entityManager()->flush();

        return $order;
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
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderItem::class),
            $entityManager->getClassMetadata(OrderEvent::class),
        ]);

        return $this->entityManager = $entityManager;
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

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }
}
