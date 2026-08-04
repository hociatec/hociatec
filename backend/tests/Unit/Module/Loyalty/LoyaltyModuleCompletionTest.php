<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Loyalty;

use App\Module\Loyalty\UI\Controller\AdminLoyaltyController;
use App\Module\Loyalty\UI\Controller\MyLoyaltyController;
use App\Module\Loyalty\Infrastructure\EventSubscriber\LoyaltyOrderSubscriber;
use App\Module\Loyalty\Application\Service\LoyaltyService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use App\Module\Voucher\Application\Service\VoucherManager;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class LoyaltyModuleCompletionTest extends TestCase
{
    public function testAdminControllerListsUpdatesAndRejectsInvalidRequests(): void
    {
        $em = $this->entityManager();
        $user = $this->user('ada@example.com');
        $user->setLoyaltyPointsBalance(250);
        $em->persist($user);
        $em->flush();

        $controller = new AdminLoyaltyController($this->userRepository($em), $this->loyalty($em));
        $list = $controller->list(Request::create('/?search=ada&page=1&perPage=5'));
        self::assertSame(Response::HTTP_OK, $list->getStatusCode());
        $listPayload = json_decode((string) $list->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ada@example.com', $listPayload['data']['items'][0]['email']);

        self::assertSame(Response::HTTP_NOT_FOUND, $controller->updateCustomer(999, Request::create('/', 'PATCH', [], [], [], [], '{"points":100}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->updateCustomer((int) $user->getId(), Request::create('/', 'PATCH', [], [], [], [], '{'))->getStatusCode());

        $updated = $controller->updateCustomer((int) $user->getId(), Request::create('/', 'PATCH', [], [], [], [], '{"points":700}'));
        self::assertSame(Response::HTTP_OK, $updated->getStatusCode());
        self::assertSame(700, $user->getLoyaltyPointsBalance());
    }

    public function testMyControllerShowsConvertsAndMapsErrors(): void
    {
        $user = $this->user('member@example.com');
        $user->setLoyaltyPointsBalance(500);
        $em = $this->entityManager();

        $controller = new MyLoyaltyController($this->loyalty($em));
        $controller->setContainer($this->container($user));

        self::assertSame(Response::HTTP_OK, $controller->show()->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->convert(Request::create('/', 'POST', [], [], [], [], '{"points":0}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->convert(Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());

        $user = $this->user('success@example.com');
        $user->setLoyaltyPointsBalance(500);
        $controller = new MyLoyaltyController($this->loyalty($this->entityManager()));
        $controller->setContainer($this->container($user));
        $converted = $controller->convert(Request::create('/', 'POST', [], [], [], [], '{"points":200}'));
        self::assertSame(Response::HTTP_CREATED, $converted->getStatusCode(), (string) $converted->getContent());
        self::assertSame(300, $user->getLoyaltyPointsBalance());
    }

    public function testSubscriberSyncsScheduledOrdersAndRecomputesChangedEntities(): void
    {
        $user = $this->user('buyer@example.com');
        $order = (new Order('ORD-LOYALTY-1', $user))->setStatus(Order::STATUS_CONFIRMED)->setTotalPriceCents(12345);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn([$order, new \stdClass()]);
        $uow->method('getScheduledEntityUpdates')->willReturn([]);
        $uow->expects(self::exactly(2))->method('recomputeSingleEntityChangeSet');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $em->method('getClassMetadata')->willReturnCallback(static fn (string $class): \Doctrine\ORM\Mapping\ClassMetadata => new \Doctrine\ORM\Mapping\ClassMetadata($class));
        $em->expects(self::once())->method('persist')->with($user);

        $subscriber = new LoyaltyOrderSubscriber($this->loyalty($this->entityManager()));
        self::assertContains(\Doctrine\ORM\Events::onFlush, $subscriber->getSubscribedEvents());
        $subscriber->onFlush(new \Doctrine\ORM\Event\OnFlushEventArgs($em));

        self::assertSame(1230, $order->getLoyaltyPointsAwarded());
        self::assertSame(1230, $user->getLoyaltyPointsBalance());
    }

    public function testConvertPointsRejectsMissingLockedUser(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $entityManager->expects(self::once())->method('find')->willReturn(null);

        $service = new LoyaltyService(
            new DoctrineUnitOfWork($entityManager),
            new DoctrineTransactionManager($entityManager),
            new VoucherManager($this->voucherRepository($this->entityManager()), new DoctrineUnitOfWork($this->entityManager())),
            $this->userRepository($this->entityManager()),
        );

        $user = $this->user('missing@example.com');
        $this->setId($user, 404);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Client introuvable.');
        $service->convertPointsToVoucher($user, 100);
    }

    private function loyalty(EntityManager $em): LoyaltyService
    {
        $persistence = new DoctrineUnitOfWork($em);

        return new LoyaltyService(
            $persistence,
            new DoctrineTransactionManager($em),
            new VoucherManager($this->voucherRepository($em), $persistence),
            $this->userRepository($em),
        );
    }

    private function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Voucher::class),
        ]);

        return $em;
    }

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function userRepository(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }

    private function voucherRepository(EntityManager $em): VoucherRepository
    {
        return new VoucherRepository($this->registry($em));
    }

    private function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    private function setId(object $entity, int $id): void
    {
        (new \ReflectionObject($entity))->getProperty('id')->setValue($entity, $id);
    }
}
