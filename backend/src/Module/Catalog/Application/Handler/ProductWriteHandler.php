<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\Service\ProductCatalogRules;
use App\Module\Catalog\Application\Service\ProductDiscountApplicator;
use App\Module\Catalog\Application\Service\ProductGalleryUpdater;
use App\Module\Catalog\Application\Service\ProductVariantBatchCreator;
use App\Module\Catalog\Application\Service\ProductVariantService;
use App\Module\Catalog\Application\Writer\ProductAttributeWriter;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ProductWriteHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private ProductCatalogRules $rules,
        private ProductVariantService $variants,
        private ProductVariantBatchCreator $variantBatch,
        private ProductGalleryUpdater $gallery,
        private ProductDiscountApplicator $discounts,
        #[Autowire(service: 'app.catalog_cache')]
        private CacheItemPoolInterface $catalogCache,
        private ProductAttributeWriter $attributes = new ProductAttributeWriter(),
    ) {
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
        $normalizedSku = strtoupper($sku);
        $resolvedVariantGroup = $this->variants->resolveVariantGroup($variantGroup, $name, $variantDefinitions);

        $this->rules->assertValidData($name, $normalizedSku, $description, $shortDescription, $priceCents, $stock);
        $this->rules->assertUniqueness($normalizedSku, null);
        $this->variants->assertDefinitionsAreUnique($resolvedVariantGroup, null, $color, $storageCapacity, $variantDefinitions);

        $product = $this->attributes->create(
            $name,
            $this->rules->resolveSlug($slug, $name, null),
            $normalizedSku,
            $description,
            $shortDescription,
            $priceCents,
            $stock,
            $isPublished,
            $isFeaturedHome,
            $category,
            $imageAlt,
            $sellingType,
            $brand,
            $resolvedVariantGroup,
            $releaseYear,
            $storageCapacity,
            $memoryRam,
            $color,
        );

        $this->discounts->applyOnCreate($product, $discountEnabled, $discountType, $discountValue, $discountStartsAt, $discountEndsAt);

        try {
            $this->gallery->update($product, $galleryFiles, []);
            $this->persistence->persist($product);
            $this->variantBatch->forNewProduct($product, $name, $sku, $slug, $resolvedVariantGroup, $stock, $variantDefinitions);
            $this->persistence->commit();
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de créer le produit.', $exception);
        }

        return $product;
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
        $normalizedSku = strtoupper($sku);
        $resolvedVariantGroup = $this->variants->resolveVariantGroup($variantGroup ?? $product->getVariantGroup(), $name, []);

        $this->rules->assertValidData($name, $normalizedSku, $description, $shortDescription, $priceCents, $stock);
        $this->rules->assertUniqueness($normalizedSku, $product->getId());
        $this->variants->assertDefinitionsAreUnique($resolvedVariantGroup, $product, $color, $storageCapacity, $variantDefinitions);

        $this->attributes->update(
            $product,
            $name,
            $this->rules->resolveSlug($slug, $name, $product->getId()),
            $normalizedSku,
            $description,
            $shortDescription,
            $priceCents,
            $stock,
            $isPublished,
            $isFeaturedHome,
            $category,
            $imageAlt,
            $sellingType,
            $brand,
            $resolvedVariantGroup,
            $releaseYear,
            $storageCapacity,
            $memoryRam,
            $color,
        );
        $this->discounts->applyOnUpdate($product, $discountEnabled, $discountType, $discountValue, $discountStartsAt, $discountEndsAt);

        if ($removeImage) {
            $galleryToRemove[] = 0;
        }

        try {
            $this->gallery->update($product, $galleryFiles, $galleryToRemove);
            $this->variantBatch->forExistingProduct($product, $name, $sku, $slug, $resolvedVariantGroup, $stock, $variantDefinitions);
            $this->persistence->commit();
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour le produit.', $exception);
        }

        return $product;
    }

    public function delete(Product $product): void
    {
        try {
            $this->persistence->remove($product);
            $this->persistence->commit();
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer le produit.', $exception);
        }
    }
}
