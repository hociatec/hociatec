<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Repository;

use App\Module\Catalog\Entity\Brand;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class ProductRepositoryTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testRepositoryQueriesCoverAdminPublishedFacetsAndMaintenanceOperations(): void
    {
        $entityManager = $this->entityManager();
        [$apple, $samsung, $phones, $hidden, $iphone, $galaxy, $hiddenProduct, $accessory] = $this->seedCatalog($entityManager);
        $repository = $this->repository($entityManager);

        $entityManager->getConnection()->beginTransaction();
        try {
            self::assertSame($iphone->getId(), $repository->findForUpdate((int) $iphone->getId())?->getId());
        } finally {
            $entityManager->getConnection()->rollBack();
        }

        $adminResults = $repository->findAllForAdmin('phones', 'galaxy', null, 'rental', 50000, 100000, false, 'price_desc', 10, 0);
        self::assertCount(1, $adminResults);
        self::assertSame($galaxy->getId(), $adminResults[0]->getId());
        self::assertSame(1, $repository->countForAdmin('phones', 'galaxy', null, 'rental', 50000, 100000, false));

        $featuredAdmin = $repository->findAllForAdmin(null, null, true, null, null, null, true, 'stock_asc', 5, 0);
        self::assertSame([$iphone->getId()], array_map(static fn (Product $product): ?int => $product->getId(), $featuredAdmin));

        $published = $repository->findPublished('phones', 'iphone', true, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 100000, 200000, true, 'relevance', 5, 0);
        self::assertCount(1, $published);
        self::assertSame($iphone->getId(), $published[0]->getId());
        self::assertSame(1, $repository->countPublished('phones', 'iphone', true, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 100000, 200000, true));

        $facets = $repository->collectPublishedFacets('phones', null, null, null, null, null, null, null, null, null, null);
        self::assertSame([
            ['value' => 'Apple', 'count' => 1],
            ['value' => 'Samsung', 'count' => 1],
        ], $facets['brands']);
        self::assertSame([
            ['value' => 'Phones', 'count' => 3, 'extra' => 'phones'],
        ], $facets['categories']);
        self::assertSame([
            ['value' => '128 Go', 'count' => 1],
            ['value' => '256 Go', 'count' => 1],
        ], $facets['storageCapacities']);
        self::assertSame(['min' => 2000, 'max' => 150000], $facets['price']);

        self::assertSame($iphone->getId(), $repository->findOnePublishedBySlug('iphone-15')?->getId());
        self::assertSame(
            [$iphone->getId(), $galaxy->getId()],
            array_map(static fn (Product $product): ?int => $product->getId(), $repository->findByVariantGroupOrdered('phones-family')),
        );
        self::assertSame(2, $repository->countByBrand($apple));

        self::assertSame(3, $repository->countLowStock(3));
        self::assertSame(
            [$accessory->getId(), $hiddenProduct->getId(), $iphone->getId()],
            array_map(static fn (Product $product): ?int => $product->getId(), $repository->findLowStock(3, 5)),
        );

        self::assertTrue($repository->existsWithSku('ip15'));
        self::assertFalse($repository->existsWithSku('ip15', (int) $iphone->getId()));
        self::assertTrue($repository->existsWithSlug('iphone-15'));
        self::assertFalse($repository->existsWithSlug('iphone-15', (int) $iphone->getId()));

        $repository->clearBrand($apple);
        $entityManager->clear();

        $reloadedIphone = $repository->findOneBy(['slug' => 'iphone-15']);
        self::assertInstanceOf(Product::class, $reloadedIphone);
        self::assertNull($reloadedIphone->getBrandReference());

        self::assertSame(0, $repository->countByBrand($apple));
        self::assertNull($repository->findOnePublishedBySlug('hidden-product'));
        self::assertFalse($hidden->isVisible());
        self::assertTrue($hiddenProduct->isPublished());
    }

    /**
     * @return array{Brand,Brand,Category,Category,Product,Product,Product,Product}
     */
    private function seedCatalog(EntityManager $entityManager): array
    {
        $apple = new Brand('Apple');
        $samsung = new Brand('Samsung');
        $phones = new Category('Phones', 'phones');
        $hidden = new Category('Hidden', 'hidden');
        $hidden->setIsVisible(false);

        $iphone = new Product('iPhone 15', 'iphone-15', 'IP15', 'Flagship phone', 150000, 2, $phones);
        $iphone
            ->setIsPublished(true)
            ->setIsFeaturedHome(true)
            ->setBrandReference($apple)
            ->setSellingType('sale')
            ->setStorageCapacity('256 Go')
            ->setMemoryRam('8 Go')
            ->setColor('Noir')
            ->setReleaseYear(2025)
            ->setVariantGroup('phones-family')
            ->setVariantPosition(1)
            ->setLowStockThreshold(3);

        $galaxy = new Product('Galaxy Rent', 'galaxy-rent', 'GAL-RENT', 'Rental phone', 90000, 10, $phones);
        $galaxy
            ->setIsPublished(true)
            ->setIsFeaturedHome(false)
            ->setBrandReference($samsung)
            ->setSellingType('rental')
            ->setStorageCapacity('128 Go')
            ->setMemoryRam('8 Go')
            ->setColor('Blanc')
            ->setReleaseYear(2024)
            ->setVariantGroup('phones-family')
            ->setVariantPosition(2)
            ->setLowStockThreshold(5);

        $hiddenProduct = new Product('Hidden Product', 'hidden-product', 'HID-1', 'Should stay hidden', 50000, 1, $hidden);
        $hiddenProduct->setIsPublished(true)->setBrandReference($apple)->setLowStockThreshold(2);

        $accessory = new Product('Cable', 'cable', 'CBL-1', 'Accessory', 2000, 1, $phones);
        $accessory->setIsPublished(true)->setLowStockThreshold(3);

        $draft = new Product('Draft Phone', 'draft-phone', 'DRF-1', 'Draft', 70000, 3, $phones);
        $draft->setIsPublished(false)->setBrandReference($samsung)->setLowStockThreshold(3);

        foreach ([$apple, $samsung, $phones, $hidden, $iphone, $galaxy, $hiddenProduct, $accessory, $draft] as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();

        return [$apple, $samsung, $phones, $hidden, $iphone, $galaxy, $hiddenProduct, $accessory];
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(Brand::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function repository(EntityManager $entityManager): ProductRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new ProductRepository($registry);
    }
}
