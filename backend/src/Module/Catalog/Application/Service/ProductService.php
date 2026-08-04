<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Service;

use App\Module\Catalog\Application\Handler\ProductWriteHandler;
use App\Module\Catalog\Application\Writer\ProductAttributeWriter;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductService
{
    private ProductWriteHandler $writer;

    public function __construct(
        DoctrineUnitOfWork $persistence,
        ProductCatalogRules $rules,
        ProductVariantService $variants,
        ProductVariantBatchCreator $variantBatch,
        ProductGalleryUpdater $gallery,
        ProductDiscountApplicator $discounts,
        #[Autowire(service: 'app.catalog_cache')]
        CacheItemPoolInterface $catalogCache,
        ProductAttributeWriter $attributes = new ProductAttributeWriter(),
        ?ProductWriteHandler $writer = null,
    ) {
        $this->writer = $writer ?? new ProductWriteHandler($persistence, $rules, $variants, $variantBatch, $gallery, $discounts, $catalogCache, $attributes);
    }

    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     * @param list<array<string, mixed>>    $variantDefinitions
     */
    public function create(
        string $name,
        string $sku,
        ?string $slug,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
        bool $isPublished,
        bool $isFeaturedHome,
        Category $category,
        array $galleryFiles,
        ?string $imageAlt,
        ?string $sellingType = 'sale',
        ?Brand $brand = null,
        ?string $variantGroup = null,
        ?int $releaseYear = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        array $variantDefinitions = [],
        ?bool $discountEnabled = null,
        ?string $discountType = null,
        ?int $discountValue = null,
        ?\DateTimeImmutable $discountStartsAt = null,
        ?\DateTimeImmutable $discountEndsAt = null,
    ): Product {
        return $this->writer->create($name, $sku, $slug, $description, $shortDescription, $priceCents, $stock, $isPublished, $isFeaturedHome, $category, $galleryFiles, $imageAlt, $sellingType, $brand, $variantGroup, $releaseYear, $storageCapacity, $memoryRam, $color, $variantDefinitions, $discountEnabled, $discountType, $discountValue, $discountStartsAt, $discountEndsAt);
    }

    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     * @param array<int, int|string>        $galleryToRemove
     * @param list<array<string, mixed>>    $variantDefinitions
     */
    public function update(
        Product $product,
        string $name,
        string $sku,
        ?string $slug,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
        bool $isPublished,
        bool $isFeaturedHome,
        Category $category,
        array $galleryFiles,
        ?string $imageAlt,
        array $galleryToRemove = [],
        bool $removeImage = false,
        ?string $sellingType = null,
        ?Brand $brand = null,
        ?string $variantGroup = null,
        ?int $releaseYear = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        array $variantDefinitions = [],
        ?bool $discountEnabled = null,
        ?string $discountType = null,
        ?int $discountValue = null,
        ?\DateTimeImmutable $discountStartsAt = null,
        ?\DateTimeImmutable $discountEndsAt = null,
    ): Product {
        return $this->writer->update($product, $name, $sku, $slug, $description, $shortDescription, $priceCents, $stock, $isPublished, $isFeaturedHome, $category, $galleryFiles, $imageAlt, $galleryToRemove, $removeImage, $sellingType, $brand, $variantGroup, $releaseYear, $storageCapacity, $memoryRam, $color, $variantDefinitions, $discountEnabled, $discountType, $discountValue, $discountStartsAt, $discountEndsAt);
    }

    public function delete(Product $product): void
    {
        $this->writer->delete($product);
    }
}
