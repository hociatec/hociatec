<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Favorite\UI\Controller\ListFavoritesController;
use App\Module\Order\Application\Workflow\CustomerOrderPortalService;
use App\Module\Order\Application\Workflow\OrderWorkflowService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\UI\Controller\CancelMyOrderController;
use App\Module\Service\Domain\Entity\ServiceOffering;
use App\Module\Service\Infrastructure\Repository\ServiceOfferingRepository;
use App\Module\Service\Application\Projection\ServiceFormatter;
use App\Module\Service\UI\Controller\PublicApi\ListServicesController;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicAndClientControllerBatchTest extends TestCase
{
    public function testPublicServicesAndFavoritesControllers(): void
    {
        $service = new ServiceOffering('Audit', 12000, 2000);
        $this->setId($service, 3);
        $service->setDescription('Desc')->setUnit('jour')->setDurationValue(2)->setDurationUnit('day');

        $services = $this->createMock(ServiceOfferingRepository::class);
        $services->expects(self::once())->method('findPublic')->with(null, 20, 20)->willReturn([$service]);
        $services->expects(self::once())->method('countPublic')->with(null)->willReturn(21);

        $publicServices = new ListServicesController($services, new ServiceFormatter());
        $servicesPayload = json_decode((string) $publicServices(new Request(['page' => '2']))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $servicesPayload['data']['meta']['page']);
        self::assertSame('2 jours', $servicesPayload['data']['items'][0]['durationLabel']);

        $user = $this->user();
        $product = (new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, new Category('Phones', 'phones')))
            ->setShortDescription('Short')
            ->setImageName('phone.jpg');
        $this->setId($product, 10);
        $favorite = new Favorite($user, $product);

        $favorites = $this->getMockBuilder(FavoriteService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listForUser', 'countForUser'])
            ->getMock();
        $favorites->expects(self::once())->method('listForUser')->with($user, 10, 0)->willReturn([$favorite]);
        $favorites->expects(self::once())->method('countForUser')->with($user)->willReturn(1);

        $controller = new class($favorites, new CatalogFormatter(), $user) extends ListFavoritesController {
            public function __construct(FavoriteService $favorites, CatalogFormatter $formatter, private readonly User $user)
            {
                parent::__construct($favorites, $formatter);
            }

            public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };

        $favoritesPayload = json_decode((string) $controller()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Phone', $favoritesPayload['data']['items'][0]['product']['name']);
        self::assertSame('/uploads/products/phone.jpg', $favoritesPayload['data']['items'][0]['product']['imageUrl']);
    }

    public function testCancelMyOrderController(): void
    {
        $owner = $this->user();
        $actor = $this->user('grace@example.com', 'Grace');
        $this->setId($owner, 1);
        $this->setId($actor, 2);

        $pendingOrder = new Order('ORD-1', $owner);
        $this->setId($pendingOrder, 7);
        $pendingOrder
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('1 rue Exemple')
            ->setShippingPostalCode('75000')
            ->setShippingCity('Paris')
            ->setBillingName('Ada Lovelace')
            ->setBillingAddress('1 rue Exemple')
            ->setBillingPostalCode('75000')
            ->setBillingCity('Paris')
            ->setBillingEmail('ada@example.com')
            ->setTotalPriceCents(12000)
            ->setSubtotalPriceCents(10000)
            ->setDiscountAmountCents(0);

        $confirmedOrder = new Order('ORD-2', $owner);
        $confirmedOrder->setStatus(Order::STATUS_CONFIRMED);

        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $pendingOrder, $confirmedOrder, $pendingOrder);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $workflow = new OrderWorkflowService(new DoctrineUnitOfWork($entityManager));

        $ratings = $this->createMock(\App\Module\Rating\Application\Port\ProductRatingRepositoryPort::class);
        $portal = new CustomerOrderPortalService(
            $orders,
            $ratings,
            new OrderAccessPolicy(),
            \App\Tests\Support\OrderFormatterFactory::create(),
            $workflow,
        );

        $controller = new class($portal, $owner) extends CancelMyOrderController {
            public function __construct(CustomerOrderPortalService $portal, private readonly User $user)
            {
                parent::__construct($portal);
            }

            public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404)->getStatusCode());

        $otherUserController = new class($portal, $actor) extends CancelMyOrderController {
            public function __construct(CustomerOrderPortalService $portal, private readonly User $user)
            {
                parent::__construct($portal);
            }

            public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };
        self::assertSame(Response::HTTP_NOT_FOUND, $otherUserController(7)->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(8)->getStatusCode());

        $payload = json_decode((string) $controller(7)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Order::STATUS_CANCELLED, $payload['data']['order']['status']);
        self::assertSame('Annulée', $payload['data']['order']['statusLabel']);
    }

    private function user(string $email = 'ada@example.com', string $firstName = 'Ada'): User
    {
        $user = new User($email, $firstName, 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
