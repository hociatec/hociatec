<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Calculator\ProductCatalogRules;
use App\Module\Catalog\Application\Cache\CatalogCacheInvalidator;
use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\DTO\ProductCoreWriteData;
use App\Module\Catalog\Application\DTO\ProductDiscountWriteData;
use App\Module\Catalog\Application\DTO\ProductGalleryWriteData;
use App\Module\Catalog\Application\DTO\ProductVariantWriteData;
use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Application\Writer\ProductDiscountApplicator;
use App\Module\Catalog\Application\Writer\ProductGalleryUpdater;
use App\Module\Catalog\Application\Factory\ProductVariantBatchCreator;
use App\Module\Catalog\Application\Handler\ProductWriteHandler;
use App\Module\Catalog\Application\Workflow\ProductVariantService;
use App\Shared\Application\TransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class ProductWriteHandlerTest extends TestCase
{
    public function testCreateNormalizesDataPersistsProductAndVariantAndClearsCache(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = new ArrayAdapter();

        $service = $this->service($productRepository, $entityManager, $cache);
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $file = new UploadedFile(__FILE__, 'gallery.php', test: true);
        $persisted = [];

        $productRepository->expects(self::once())
            ->method('existsWithSku')
            ->with('SKU-NEW', null)
            ->willReturn(false);
        $productRepository->expects(self::once())
            ->method('findByVariantGroupOrdered')
            ->with('New Phone')
            ->willReturn([]);
        $productRepository->expects(self::exactly(2))
            ->method('existsWithSlug')
            ->willReturnCallback(static function (string $slug, ?int $excludeId): bool {
                TestCase::assertNull($excludeId);

                return match ($slug) {
                    'new-phone' => false,
                    'new-phone-black-256-go' => false,
                    default => throw new \LogicException('Unexpected slug lookup: '.$slug),
                };
            });

        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $product = $service->create(ProductWriteCommand::forCreate(
            new ProductCoreWriteData(
                name: 'New Phone',
                sku: 'sku-new',
                slug: null,
                description: 'Detailed description',
                shortDescription: 'Short description',
                priceCents: 129900,
                stock: 5,
                isPublished: true,
                isFeaturedHome: true,
                category: $category,
                imageAlt: 'Front shot',
                sellingType: 'sale',
                brand: $brand,
            ),
            new ProductGalleryWriteData(files: [$file, null, null, null]),
            new ProductVariantWriteData(
                group: null,
                releaseYear: 2026,
                storageCapacity: null,
                memoryRam: '8 Go',
                color: null,
                definitions: [['color' => ' Black ', 'storageCapacity' => ' 256 Go ', 'stock' => 3]],
            ),
            new ProductDiscountWriteData(
                enabled: true,
                type: 'fixed_cents',
                value: 1500,
                startsAt: new \DateTimeImmutable('2026-07-01'),
                endsAt: new \DateTimeImmutable('2026-07-31'),
            ),
        ));

        self::assertCount(2, $persisted);
        self::assertSame($product, $persisted[0]);
        self::assertInstanceOf(Product::class, $persisted[1]);
        self::assertSame('SKU-NEW', $product->getSku());
        self::assertSame('new-phone', $product->getSlug());
        self::assertSame('New Phone', $product->getVariantGroup());
        self::assertSame(1, $product->getVariantPosition());
        self::assertSame('sale', $product->getSellingType());
        self::assertTrue($product->isDiscountEnabled());
        self::assertSame('fixed_cents', $product->getDiscountType());
        self::assertSame(1500, $product->getDiscountValue());
        self::assertSame($file, $product->getImageFile());
        self::assertSame('Front shot', $product->getImageAlt());
        self::assertSame($brand, $product->getBrandReference());

        $variant = $persisted[1];
        self::assertInstanceOf(Product::class, $variant);
        self::assertSame('New Phone (Black) (256 Go)', $variant->getName());
        self::assertSame('SKU-NEW-BLACK-256-GO', $variant->getSku());
        self::assertSame('new-phone-black-256-go', $variant->getSlug());
        self::assertSame('New Phone', $variant->getVariantGroup());
        self::assertSame(2, $variant->getVariantPosition());
        self::assertSame(3, $variant->getStock());
        self::assertSame('Black', $variant->getColor());
        self::assertSame('256 Go', $variant->getStorageCapacity());
        self::assertNull($variant->getImageName());
        self::assertSame(2, (new CatalogCacheVersion($cache, new NullLogger()))->current());
    }

    public function testUpdateAppliesRequestedChangesCreatesNewVariantAndRemovesMainImage(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = new ArrayAdapter();

        $service = $this->service($productRepository, $entityManager, $cache);
        $category = new Category('Phones', 'phones');
        $newCategory = new Category('Premium', 'premium');
        $brand = new Brand('Samsung');
        $product = new Product('Old Phone', 'old-phone', 'OLD-SKU', 'Old desc', 99900, 1, $category);
        $this->setId($product, 12);
        $product
            ->setVariantGroup('Legacy Group')
            ->setImageName('old-main.jpg')
            ->setGalleryImage2Name('old-side.jpg')
            ->setGalleryImage3Name('old-back.jpg')
            ->setDiscountEnabled(true)
            ->setDiscountType('percent')
            ->setDiscountValue(10);
        $this->setEmbeddedProperty($product, 'characteristics', 'variantPosition', 0);

        $persisted = [];

        $productRepository->expects(self::once())
            ->method('existsWithSku')
            ->with('SKU-UPD', 12)
            ->willReturn(false);
        $productRepository->expects(self::exactly(2))
            ->method('findByVariantGroupOrdered')
            ->with('Group A')
            ->willReturn([$product]);
        $productRepository->expects(self::exactly(2))
            ->method('existsWithSlug')
            ->willReturnCallback(static function (string $slug, ?int $excludeId): bool {
                return match ($slug) {
                    'updated-phone' => 12 === $excludeId ? false : throw new \LogicException('Unexpected exclude id'),
                    'updated-phone-silver-512-go' => null === $excludeId ? false : throw new \LogicException('Unexpected exclude id'),
                    default => throw new \LogicException('Unexpected slug lookup: '.$slug),
                };
            });

        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $updated = $service->update(ProductWriteCommand::forUpdate(
            $product,
            new ProductCoreWriteData(
                name: 'Updated Phone',
                sku: 'sku-upd',
                slug: ' updated-phone ',
                description: 'New desc',
                shortDescription: 'Short',
                priceCents: 149900,
                stock: 8,
                isPublished: false,
                isFeaturedHome: true,
                category: $newCategory,
                imageAlt: 'Updated alt',
                sellingType: 'rental',
                brand: $brand,
            ),
            new ProductGalleryWriteData(files: [], toRemove: [2], removeMainImage: true),
            new ProductVariantWriteData(
                group: ' Group A ',
                releaseYear: 2025,
                storageCapacity: '256 Go',
                memoryRam: '12 Go',
                color: 'Gold',
                definitions: [['color' => 'Silver', 'storageCapacity' => '512 Go', 'stock' => 6]],
            ),
            new ProductDiscountWriteData(
                enabled: false,
                type: 'percent',
                value: 20,
                startsAt: null,
                endsAt: new \DateTimeImmutable('2026-08-31'),
            ),
        ));

        self::assertSame($product, $updated);
        self::assertSame('Updated Phone', $updated->getName());
        self::assertSame('updated-phone', $updated->getSlug());
        self::assertSame('SKU-UPD', $updated->getSku());
        self::assertSame('New desc', $updated->getDescription());
        self::assertSame('Short', $updated->getShortDescription());
        self::assertSame(149900, $updated->getPriceCents());
        self::assertSame(8, $updated->getStock());
        self::assertFalse($updated->isPublished());
        self::assertTrue($updated->isFeaturedHome());
        self::assertSame($newCategory, $updated->getCategory());
        self::assertNull($updated->getImageAlt());
        self::assertSame($brand, $updated->getBrandReference());
        self::assertSame('Group A', $updated->getVariantGroup());
        self::assertSame(1, $updated->getVariantPosition());
        self::assertSame('256 Go', $updated->getStorageCapacity());
        self::assertSame('12 Go', $updated->getMemoryRam());
        self::assertSame('Gold', $updated->getColor());
        self::assertSame('rental', $updated->getSellingType());
        self::assertFalse($updated->isDiscountEnabled());
        self::assertSame('percent', $updated->getDiscountType());
        self::assertSame(20, $updated->getDiscountValue());
        self::assertNull($updated->getDiscountStartsAt());
        self::assertSame('2026-08-31', $updated->getDiscountEndsAt()?->format('Y-m-d'));
        self::assertNull($updated->getImageName());
        self::assertNull($updated->getGalleryImage3Name());

        self::assertCount(1, $persisted);
        $variant = $persisted[0];
        self::assertInstanceOf(Product::class, $variant);
        self::assertSame('Updated Phone (Silver) (512 Go)', $variant->getName());
        self::assertSame('SKU-UPD-SILVER-512-GO', $variant->getSku());
        self::assertSame('updated-phone-silver-512-go', $variant->getSlug());
        self::assertSame(2, $variant->getVariantPosition());
        self::assertSame(6, $variant->getStock());
        self::assertSame(2, (new CatalogCacheVersion($cache, new NullLogger()))->current());
    }

    public function testDeleteRemovesProductFlushesAndClearsCache(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = new ArrayAdapter();
        $service = $this->service($productRepository, $entityManager, $cache);
        $product = new Product('Phone', 'phone', 'SKU', 'Desc', 1000, 1, new Category('Phones', 'phones'));

        $entityManager->expects(self::once())->method('remove')->with($product);
        $entityManager->expects(self::once())->method('flush');

        $service->delete($product);
        self::assertSame(2, (new CatalogCacheVersion($cache, new NullLogger()))->current());
    }

    public function testCommittedProductWriteDoesNotFailWhenCacheInvalidationFails(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = new class extends ArrayAdapter {
            public function getItem(mixed $key): \Symfony\Component\Cache\CacheItem
            {
                throw new \RuntimeException('cache down');
            }
        };

        $service = $this->service($productRepository, $entityManager, $cache);
        $category = new Category('Phones', 'phones');

        $productRepository->method('existsWithSku')->willReturn(false);
        $productRepository->method('findByVariantGroupOrdered')->willReturn([]);
        $productRepository->method('existsWithSlug')->willReturn(false);

        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $product = $service->create(ProductWriteCommand::forCreate(
            new ProductCoreWriteData(
                name: 'Cache Safe Phone',
                sku: 'cache-safe',
                slug: null,
                description: 'Description',
                shortDescription: null,
                priceCents: 1000,
                stock: 1,
                isPublished: true,
                isFeaturedHome: false,
                category: $category,
                imageAlt: null,
                sellingType: 'sale',
                brand: null,
            ),
            new ProductGalleryWriteData(files: []),
            new ProductVariantWriteData(
                group: null,
                releaseYear: null,
                storageCapacity: null,
                memoryRam: null,
                color: null,
                definitions: [],
            ),
            new ProductDiscountWriteData(
                enabled: false,
                type: null,
                value: null,
                startsAt: null,
                endsAt: null,
            ),
        ));

        self::assertSame('CACHE-SAFE', $product->getSku());
    }

    private function service(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        CacheItemPoolInterface $cache,
    ): ProductWriteHandler {
        $rules = new ProductCatalogRules($productRepository, Validation::createValidator());
        $variants = new ProductVariantService(
            new \App\Module\Catalog\Application\Factory\ProductVariantFactory($productRepository, $rules),
            new \App\Module\Catalog\Application\Policy\ProductVariantIdentityPolicy($productRepository),
        );
        $persistence = new DoctrineUnitOfWork($entityManager);
        $variantBatch = new ProductVariantBatchCreator($variants, $productRepository, $persistence);

        $transactions = new class($entityManager) implements TransactionManager {
            public function __construct(private EntityManagerInterface $entityManager)
            {
            }

            public function transactional(\Closure $operation): mixed
            {
                $result = $operation();
                $this->entityManager->flush();

                return $result;
            }
        };

        return new ProductWriteHandler(
            new \App\Module\Catalog\Application\Handler\ProductWriteExecution(
                $persistence,
                $transactions,
                new CatalogCacheInvalidator(new CatalogCacheVersion($cache, new NullLogger())),
            ),
            $rules,
            $variants,
            $variantBatch,
            new ProductGalleryUpdater(),
            new ProductDiscountApplicator(),
            new \App\Module\Catalog\Application\Writer\ProductAttributeWriter(),
        );
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private function setProperty(object $entity, string $property, mixed $value): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty($property)->setValue($entity, $value);
    }

    private function setEmbeddedProperty(object $entity, string $embeddedProperty, string $property, mixed $value): void
    {
        $reflection = new \ReflectionObject($entity);
        $embedded = $reflection->getProperty($embeddedProperty)->getValue($entity);
        self::assertIsObject($embedded);

        $embeddedReflection = new \ReflectionObject($embedded);
        $embeddedReflection->getProperty($property)->setValue($embedded, $value);
    }
}
