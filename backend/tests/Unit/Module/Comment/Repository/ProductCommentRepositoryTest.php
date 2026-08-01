<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Comment\Repository;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Comment\Entity\ProductComment;
use App\Module\Comment\Repository\ProductCommentRepository;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Rating\Entity\ProductRating;
use App\Module\User\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class ProductCommentRepositoryTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testRepositoryCanPersistAndFindProductComments(): void
    {
        [$rating, $comment] = $this->seedComment();
        $repository = $this->repository();

        self::assertSame($comment, $repository->find($comment->getId()));
        self::assertSame($comment, $repository->findOneBy(['rating' => $rating]));
        self::assertSame([$comment], $repository->findBy(['isVisible' => true]));

        $comment->setIsVisible(false);
        $this->entityManager()->flush();

        self::assertSame([], $repository->findBy(['isVisible' => true]));
        self::assertSame([$comment], $repository->findBy(['isVisible' => false]));
    }

    public function testProductCommentMutatorsAndLifecycle(): void
    {
        [$rating, $comment] = $this->seedComment();
        $createdAt = $comment->getCreatedAt();
        $updatedAt = $comment->getUpdatedAt();

        self::assertNotNull($comment->getId());
        self::assertSame($rating, $comment->getRating());
        self::assertSame('Excellent', $comment->getBody());
        self::assertTrue($comment->isVisible());
        self::assertSame($createdAt, $updatedAt);

        self::assertSame($comment, $comment->setBody('Updated'));
        self::assertSame($comment, $comment->setIsVisible(false));

        $comment->touch();

        self::assertSame('Updated', $comment->getBody());
        self::assertFalse($comment->isVisible());
        self::assertGreaterThanOrEqual($updatedAt, $comment->getUpdatedAt());
    }

    /** @return array{ProductRating,ProductComment} */
    private function seedComment(): array
    {
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $order = new Order('ORD-COMMENT-1', $user);
        $orderItem = (new OrderItem('Phone', 'PH-1', 10000, 1))->setProduct($product);
        $order->addItem($orderItem);
        $rating = new ProductRating($product, $orderItem, $user, 5);
        $comment = new ProductComment($rating, 'Excellent');
        $rating->setComment($comment);

        foreach ([$user, $category, $product, $order, $orderItem, $rating, $comment] as $entity) {
            $this->entityManager()->persist($entity);
        }
        $this->entityManager()->flush();

        return [$rating, $comment];
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderItem::class),
            $entityManager->getClassMetadata(ProductRating::class),
            $entityManager->getClassMetadata(ProductComment::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function repository(): ProductCommentRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new ProductCommentRepository($registry);
    }
}
