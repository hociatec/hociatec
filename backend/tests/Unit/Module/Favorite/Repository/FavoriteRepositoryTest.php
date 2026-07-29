<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Favorite\Repository;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Favorite\Entity\Favorite;
use App\Module\Favorite\Repository\FavoriteRepository;
use App\Module\User\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class FavoriteRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([
            $this->entityManager->getClassMetadata(Category::class),
            $this->entityManager->getClassMetadata(Product::class),
            $this->entityManager->getClassMetadata(User::class),
            $this->entityManager->getClassMetadata(Favorite::class),
        ]);
    }

    public function testFavoriteQueriesReturnExpectedRows(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $other = new User('grace@example.com', 'Grace', 'Hopper', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $other->setPassword('hashed');
        $category = new Category('Phones', 'phones');
        $productA = new Product('Phone A', 'phone-a', 'PHA', 'Desc', 10000, 5, $category);
        $productB = new Product('Phone B', 'phone-b', 'PHB', 'Desc', 10000, 5, $category);
        $favoriteA = new Favorite($user, $productA);
        $favoriteB = new Favorite($user, $productB);
        $ignored = new Favorite($other, $productA);

        $this->setDate($favoriteA, 'createdAt', new \DateTimeImmutable('2026-07-10T10:00:00+00:00'));
        $this->setDate($favoriteB, 'createdAt', new \DateTimeImmutable('2026-07-20T10:00:00+00:00'));

        foreach ([$user, $other, $category, $productA, $productB, $favoriteA, $favoriteB, $ignored] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $repository = $this->repository();

        self::assertSame($favoriteA->getId(), $repository->findOneByUserAndProduct($user, $productA)?->getId());
        self::assertTrue($repository->existsForUserAndProduct($user, $productB));
        self::assertFalse($repository->existsForUserAndProduct($other, $productB));

        $favorites = $repository->findFavoritesForUser($user);
        self::assertCount(2, $favorites);
        self::assertSame($favoriteB->getId(), $favorites[0]->getId());
        self::assertSame($favoriteA->getId(), $favorites[1]->getId());
    }

    private function repository(): FavoriteRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new FavoriteRepository($registry);
    }

    private function setDate(object $entity, string $property, \DateTimeImmutable $value): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty($property)->setValue($entity, $value);
    }
}
