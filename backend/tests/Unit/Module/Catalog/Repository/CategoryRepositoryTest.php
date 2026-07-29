<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Repository;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Repository\CategoryRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class CategoryRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([$this->entityManager->getClassMetadata(Category::class)]);
    }

    public function testCategoryQueriesReturnExpectedResults(): void
    {
        $visibleA = (new Category('Audio', 'audio'))->setIsVisible(true);
        $visibleB = (new Category('Phones', 'phones'))->setIsVisible(true);
        $hidden = (new Category('Hidden', 'hidden'))->setIsVisible(false);

        foreach ([$visibleB, $hidden, $visibleA] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $repository = $this->repository();

        self::assertSame(['Audio', 'Phones'], array_map(static fn (Category $c): string => $c->getName(), $repository->findAllVisibleOrdered()));
        self::assertSame(['Audio', 'Hidden', 'Phones'], array_map(static fn (Category $c): string => $c->getName(), $repository->findAllForAdmin()));
        self::assertSame($visibleB->getId(), $repository->findOneVisibleBySlug('phones')?->getId());
        self::assertNull($repository->findOneVisibleBySlug('hidden'));
        self::assertTrue($repository->existsWithSlug('phones'));
        self::assertFalse($repository->existsWithSlug('missing'));
        self::assertFalse($repository->existsWithSlug('phones', $visibleB->getId()));
        self::assertTrue($repository->existsWithName('phones'));
        self::assertFalse($repository->existsWithName('phones', $visibleB->getId()));
    }

    private function repository(): CategoryRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new CategoryRepository($registry);
    }
}
