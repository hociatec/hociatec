<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductService
{
    public function __construct(
        private readonly DoctrineUnitOfWork $persistence,
        private readonly ProductCatalogRules $rules,
        private readonly ProductVariantService $variants,
        private readonly ProductVariantBatchCreator $variantBatch,
        private readonly ProductGalleryManager $gallery,
        private readonly ProductDiscountApplicator $discounts,
        #[Autowire(service: 'app.catalog_cache')]
        private readonly CacheItemPoolInterface $catalogCache,
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

        $resolvedSlug = $this->rules->resolveSlug($slug, $name, null);

        $product = new Product(
            $name,
            $resolvedSlug,
            $normalizedSku,
            $description,
            $priceCents,
            $stock,
            $category,
        );

        $product
            ->setShortDescription($shortDescription)
            ->setIsPublished($isPublished)
            ->setIsFeaturedHome($isFeaturedHome)
            ->setImageAlt($imageAlt)
            ->setBrandReference($brand)
            ->setVariantGroup($resolvedVariantGroup)
            ->setVariantPosition(1)
            ->setReleaseYear($releaseYear)
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($memoryRam)
            ->setColor($color);

        if (null !== $sellingType) {
            $product->setSellingType($sellingType);
        }

        $this->discounts->applyOnCreate($product, $discountEnabled, $discountType, $discountValue, $discountStartsAt, $discountEndsAt);

        try {
            $this->gallery->update($product, $galleryFiles, []);

            $this->persistence->persist($product);
            $this->variantBatch->forNewProduct(
                $product,
                $name,
                $sku,
                $slug,
                $resolvedVariantGroup,
                $stock,
                $variantDefinitions,
            );

            $this->persistence->flush();
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

        $resolvedSlug = $this->rules->resolveSlug($slug, $name, $product->getId());

        $product
            ->setName($name)
            ->setSlug($resolvedSlug)
            ->setSku($normalizedSku)
            ->setDescription($description)
            ->setShortDescription($shortDescription)
            ->setPriceCents($priceCents)
            ->setStock($stock)
            ->setIsPublished($isPublished)
            ->setIsFeaturedHome($isFeaturedHome)
            ->setCategory($category)
            ->setImageAlt($imageAlt)
            ->setBrandReference($brand)
            ->setVariantGroup($resolvedVariantGroup)
            ->setVariantPosition($product->getVariantPosition() > 0 ? $product->getVariantPosition() : 1)
            ->setReleaseYear($releaseYear)
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($memoryRam)
            ->setColor($color);

        if (null !== $sellingType) {
            $product->setSellingType($sellingType);
        }

        $this->discounts->applyOnUpdate($product, $discountEnabled, $discountType, $discountValue, $discountStartsAt, $discountEndsAt);

        if ($removeImage) {
            $galleryToRemove[] = 0;
        }

        try {
            $this->gallery->update($product, $galleryFiles, $galleryToRemove);
            $this->variantBatch->forExistingProduct(
                $product,
                $name,
                $sku,
                $slug,
                $resolvedVariantGroup,
                $stock,
                $variantDefinitions,
            );

            $this->persistence->flush();
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
            $this->persistence->flush();
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer le produit.', $exception);
        }
    }
}
