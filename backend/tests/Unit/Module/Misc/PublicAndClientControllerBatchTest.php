<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\UI\Controller\ListFavoritesController;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Order\UI\Controller\CancelMyOrderController;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Infrastructure\Persistence\OrderPersistence;
use App\Module\Order\Application\Workflow\OrderWorkflowService;
use App\Module\Quote\UI\Controller\PublicApi\ListServicesController;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Module\Quote\Infrastructure\Repository\ServiceOfferingRepository;
use App\Module\User\Domain\Entity\User;
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
        $services->expects(self::once())->method('findPaginated')->with(20, 20)->willReturn([$service]);
        $services->expects(self::once())->method('countAll')->willReturn(21);

        $publicServices = new ListServicesController($services);
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
            ->onlyMethods(['listForUser'])
            ->getMock();
        $favorites->expects(self::once())->method('listForUser')->with($user)->willReturn([$favorite]);

        $controller = new class($favorites, $user) extends ListFavoritesController {
            public function __construct(FavoriteService $favorites, private readonly User $user)
            {
                parent::__construct($favorites);
            }

            public function getUser(): ?User
            {
                return $this->user;
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
        $workflow = new OrderWorkflowService(new OrderPersistence($entityManager));

        $controller = new class($orders, $workflow, $owner) extends CancelMyOrderController {
            public function __construct(OrderRepository $orders, OrderWorkflowService $workflow, private readonly User $user)
            {
                parent::__construct($orders, $workflow, new OrderAccessPolicy());
            }

            public function getUser(): ?User
            {
                return $this->user;
            }
        };

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404)->getStatusCode());

        $otherUserController = new class($orders, $workflow, $actor) extends CancelMyOrderController {
            public function __construct(OrderRepository $orders, OrderWorkflowService $workflow, private readonly User $user)
            {
                parent::__construct($orders, $workflow, new OrderAccessPolicy());
            }

            public function getUser(): ?User
            {
                return $this->user;
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
