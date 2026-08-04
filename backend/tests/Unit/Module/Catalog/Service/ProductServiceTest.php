<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Service\ProductCatalogRules;
use App\Module\Catalog\Application\Service\ProductDiscountApplicator;
use App\Module\Catalog\Application\Service\ProductGalleryManager;
use App\Module\Catalog\Application\Service\ProductService;
use App\Module\Catalog\Application\Service\ProductVariantBatchCreator;
use App\Module\Catalog\Application\Service\ProductVariantService;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class ProductServiceTest extends TestCase
{
    public function testCreateNormalizesDataPersistsProductAndVariantAndClearsCache(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = $this->createMock(CacheItemPoolInterface::class);

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
        $cache->expects(self::once())->method('clear')->willReturn(true);

        $product = $service->create(
            'New Phone',
            'sku-new',
            null,
            'Detailed description',
            'Short description',
            129900,
            5,
            true,
            true,
            $category,
            [$file, null, null, null],
            'Front shot',
            'sale',
            $brand,
            null,
            2026,
            null,
            '8 Go',
            null,
            [['color' => ' Black ', 'storageCapacity' => ' 256 Go ', 'stock' => 3]],
            true,
            'fixed_cents',
            1500,
            new \DateTimeImmutable('2026-07-01'),
            new \DateTimeImmutable('2026-07-31'),
        );

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
    }

    public function testUpdateAppliesRequestedChangesCreatesNewVariantAndRemovesMainImage(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = $this->createMock(CacheItemPoolInterface::class);

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
        $this->setProperty($product, 'variantPosition', 0);

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
        $cache->expects(self::once())->method('clear')->willReturn(true);

        $updated = $service->update(
            $product,
            'Updated Phone',
            'sku-upd',
            ' updated-phone ',
            'New desc',
            'Short',
            149900,
            8,
            false,
            true,
            $newCategory,
            [],
            'Updated alt',
            [2],
            true,
            'rental',
            $brand,
            ' Group A ',
            2025,
            '256 Go',
            '12 Go',
            'Gold',
            [['color' => 'Silver', 'storageCapacity' => '512 Go', 'stock' => 6]],
            false,
            'percent',
            20,
            null,
            new \DateTimeImmutable('2026-08-31'),
        );

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
    }

    public function testDeleteRemovesProductFlushesAndClearsCache(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $service = $this->service($productRepository, $entityManager, $cache);
        $product = new Product('Phone', 'phone', 'SKU', 'Desc', 1000, 1, new Category('Phones', 'phones'));

        $entityManager->expects(self::once())->method('remove')->with($product);
        $entityManager->expects(self::once())->method('flush');
        $cache->expects(self::once())->method('clear')->willReturn(true);

        $service->delete($product);
    }

    private function service(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        CacheItemPoolInterface $cache,
    ): ProductService {
        $rules = new ProductCatalogRules($productRepository, Validation::createValidator());
        $variants = new ProductVariantService($productRepository, $rules);
        $persistence = new DoctrineUnitOfWork($entityManager);
        $variantBatch = new ProductVariantBatchCreator($variants, $productRepository, $persistence);

        return new ProductService(
            $persistence,
            $rules,
            $variants,
            $variantBatch,
            new ProductGalleryManager(),
            new ProductDiscountApplicator(),
            $cache,
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
}
