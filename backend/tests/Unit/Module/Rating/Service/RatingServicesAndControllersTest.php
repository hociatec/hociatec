<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Rating\Service;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Comment\Domain\Entity\ProductComment;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Infrastructure\Repository\OrderItemRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Rating\UI\Controller\CreateProductReviewController;
use App\Module\Rating\UI\Controller\ListProductReviewsController;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\Rating\Application\Exception\ProductReviewException;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;
use App\Module\Rating\Application\Provider\PendingReviewResolver;
use App\Module\Rating\Application\Workflow\ProductRatingService;
use App\Module\Rating\Application\Writer\ProductReviewStatsUpdater;
use App\Module\Rating\Infrastructure\Persistence\RatingPersistence;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RatingServicesAndControllersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testCreateReviewPersistsRatingCommentAndRefreshesProductStats(): void
    {
        [$user, $product, $order, $item] = $this->persistDeliveredOrderWithProduct();

        $rating = $this->ratingService()->createReview($user, $order, $item, 5, '  Excellent suivi  ');

        self::assertSame(5, $rating->getScore());
        self::assertSame(ProductRating::STATUS_PUBLISHED, $rating->getStatus());
        self::assertSame('Excellent suivi', $rating->getComment()?->getBody());
        self::assertSame(1, $product->getReviewsCount());
        self::assertSame(5.0, $product->getReviewsAverage());
        self::assertNotNull($this->ratingRepository()->find($rating->getId()));
    }

    public function testCreateReviewRejectsInvalidBusinessCases(): void
    {
        [$user, , $order, $item] = $this->persistDeliveredOrderWithProduct();
        $otherUser = $this->persistUser('other@example.test');

        $this->expectReviewException('Commande introuvable.', fn () => $this->ratingService()->createReview($otherUser, $order, $item, 5, null));

        $order->setStatus(Order::STATUS_CONFIRMED);
        $this->expectReviewException('Vous pourrez laisser un avis une fois la commande livr', fn () => $this->ratingService()->createReview($user, $order, $item, 5, null));
        $order->setStatus(Order::STATUS_DELIVERED);

        $itemWithoutProduct = new OrderItem('Deleted', 'DEL', 1000, 1);
        $order->addItem($itemWithoutProduct);
        $this->entityManager()->persist($itemWithoutProduct);
        $this->entityManager()->flush();
        $this->expectReviewException('Produit introuvable.', fn () => $this->ratingService()->createReview($user, $order, $itemWithoutProduct, 5, null));

        $this->expectReviewException('Note invalide.', fn () => $this->ratingService()->createReview($user, $order, $item, 6, null));

        $this->ratingService()->createReview($user, $order, $item, 4, null);
        $this->expectReviewException('Vous avez d', fn () => $this->ratingService()->createReview($user, $order, $item, 4, null));
    }

    public function testPendingReviewResolverKeepsOnlyDeliveredUnreviewedProductItems(): void
    {
        [$user, $product, $order, $pendingItem] = $this->persistDeliveredOrderWithProduct();
        $productId = $product->getId();
        $orderId = $order->getId();
        $pendingItemId = $pendingItem->getId();
        self::assertNotNull($productId);
        self::assertNotNull($orderId);
        self::assertNotNull($pendingItemId);

        $reviewedItem = (new OrderItem('Phone 2', 'PH-2', 12000, 1))->setProduct($product);
        $order->addItem($reviewedItem);
        $this->entityManager()->persist($reviewedItem);
        $this->entityManager()->persist(new ProductRating($product, $reviewedItem, $user, 4));

        $otherOrder = new Order('ORD-PENDING', $user);
        $otherOrder->setStatus(Order::STATUS_CONFIRMED);
        $otherItem = (new OrderItem('Phone 3', 'PH-3', 13000, 1))->setProduct($product);
        $otherOrder->addItem($otherItem);
        $this->entityManager()->persist($otherOrder);
        $this->entityManager()->persist($otherItem);
        $this->entityManager()->flush();

        $pending = (new PendingReviewResolver($this->repository(OrderItemRepository::class)))->resolve($user);

        self::assertCount(1, $pending);
        self::assertSame($orderId, $pending[0]['orderId']);
        self::assertSame('ORD-1', $pending[0]['orderNumber']);
        self::assertSame($pendingItemId, $pending[0]['orderItemId']);
        self::assertSame(['id' => $productId, 'name' => 'Phone', 'sku' => 'PH-1'], $pending[0]['product']);
    }

    public function testListProductReviewsControllerHandlesMissingProductAndPaginationBounds(): void
    {
        [$user, $product, $order, $item] = $this->persistDeliveredOrderWithProduct();
        $itemId = $item->getId();
        $rating = new ProductRating($product, $item, $user, 5);
        $rating->publish();
        $this->entityManager()->persist($rating);
        $product->setReviewsCount(1)->setReviewsAverage(5);
        $this->entityManager()->flush();
        $ratingId = $rating->getId();

        $controller = new ListProductReviewsController(
            new ProductQueryService($this->repository(ProductRepository::class)),
            $this->ratingRepository(),
        );

        $missing = $controller('missing', new Request());
        self::assertSame(Response::HTTP_NOT_FOUND, $missing->getStatusCode());

        $response = $controller('phone', new Request(['page' => '-3', 'perPage' => '200']));
        $payload = $this->json($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, $payload['data']['meta']['page']);
        self::assertSame(50, $payload['data']['meta']['perPage']);
        self::assertSame(1, $payload['data']['meta']['total']);
        self::assertEquals(5.0, $payload['data']['meta']['average']);
        self::assertSame($ratingId, $payload['data']['items'][0]['id']);
    }

    public function testCreateProductReviewControllerMapsNotFoundBadRequestAndSuccess(): void
    {
        [$user, , $order, $item] = $this->persistDeliveredOrderWithProduct();
        $orderId = $order->getId();
        $itemId = $item->getId();
        self::assertNotNull($orderId);
        self::assertNotNull($itemId);

        $controller = new class($this->repository(OrderRepository::class), $this->ratingService(), $user) extends CreateProductReviewController {
            public function __construct(OrderRepository $orders, ProductRatingService $reviews, private readonly User $user)
            {
                parent::__construct($orders, $reviews, new OrderAccessPolicy());
            }

            protected function getUser(): User
            {
                return $this->user;
            }
        };

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404, $itemId, new Request())->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $controller($orderId, 404, new Request())->getStatusCode());

        $bad = $controller($orderId, $itemId, new Request([], [], [], [], [], [], '{"score":0}'));
        self::assertSame(Response::HTTP_BAD_REQUEST, $bad->getStatusCode());

        $ok = $controller($orderId, $itemId, new Request([], [], [], [], [], [], '{"score":5,"comment":"Top"}'));
        $payload = $this->json($ok);
        self::assertSame(Response::HTTP_OK, $ok->getStatusCode());
        self::assertSame(5, $payload['data']['review']['score']);
        self::assertSame($itemId, $payload['data']['review']['orderItemId']);
    }

    private function ratingService(): ProductRatingService
    {
        $entityManager = $this->entityManager();
        $ratingRepository = $this->ratingRepository();

        return new ProductRatingService(
            $ratingRepository,
            new ProductReviewStatsUpdater($ratingRepository, new DoctrineUnitOfWork($entityManager)),
            new RatingPersistence($entityManager),
        );
    }

    /** @return array{User,Product,Order,OrderItem} */
    private function persistDeliveredOrderWithProduct(): array
    {
        $user = $this->persistUser('ada@example.test');
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $order = new Order('ORD-1', $user);
        $order->setStatus(Order::STATUS_DELIVERED);
        $item = (new OrderItem('Phone', 'PH-1', 10000, 1))->setProduct($product);
        $order->addItem($item);

        foreach ([$category, $product, $order, $item] as $entity) {
            $this->entityManager()->persist($entity);
        }
        $this->entityManager()->flush();

        return [$user, $product, $order, $item];
    }

    private function persistUser(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    /** @param \Closure(): mixed $callback */
    private function expectReviewException(string $messagePart, \Closure $callback): void
    {
        try {
            $callback();
            self::fail('ProductReviewException was not thrown.');
        } catch (ProductReviewException $exception) {
            self::assertStringContainsString($messagePart, $exception->getMessage());
        }
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Brand::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderItem::class),
            $entityManager->getClassMetadata(ProductRating::class),
            $entityManager->getClassMetadata(ProductComment::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     *
     * @return T
     */
    private function repository(string $repositoryClass): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new $repositoryClass($registry);
    }

    private function ratingRepository(): ProductRatingRepository
    {
        return $this->repository(ProductRatingRepository::class);
    }

    /** @return array<string, mixed> */
    private function json(\Symfony\Component\HttpFoundation\JsonResponse $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

}
