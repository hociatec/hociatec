<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\Calculator\ProductCatalogRules;
use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Application\Factory\ProductVariantBatchCreator;
use App\Module\Catalog\Application\Workflow\ProductVariantService;
use App\Module\Catalog\Application\Writer\ProductAttributeWriter;
use App\Module\Catalog\Application\Writer\ProductDiscountApplicator;
use App\Module\Catalog\Application\Writer\ProductGalleryUpdater;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProductWriteHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
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

    public function create(ProductWriteCommand $command): Product
    {
        $normalizedSku = strtoupper($command->sku);
        $resolvedVariantGroup = $this->variants->resolveVariantGroup($command->variantGroup, $command->name, $command->variantDefinitions);

        $this->rules->assertValidData($command->name, $normalizedSku, $command->description, $command->shortDescription, $command->priceCents, $command->stock);
        $this->rules->assertUniqueness($normalizedSku, null);
        $this->variants->assertDefinitionsAreUnique($resolvedVariantGroup, null, $command->color, $command->storageCapacity, $command->variantDefinitions);

        $product = $this->attributes->create(
            $command->name,
            $this->rules->resolveSlug($command->slug, $command->name, null),
            $normalizedSku,
            $command->description,
            $command->shortDescription,
            $command->priceCents,
            $command->stock,
            $command->isPublished,
            $command->isFeaturedHome,
            $command->category,
            $command->imageAlt,
            $command->sellingType,
            $command->brand,
            $resolvedVariantGroup,
            $command->releaseYear,
            $command->storageCapacity,
            $command->memoryRam,
            $command->color,
        );

        $this->discounts->applyOnCreate($product, $command->discountEnabled, $command->discountType, $command->discountValue, $command->discountStartsAt, $command->discountEndsAt);

        try {
            $this->transactions->transactional(function () use ($product, $command, $resolvedVariantGroup): void {
                $this->gallery->update($product, $command->galleryFiles, []);
                $this->persistence->persist($product);
                $this->variantBatch->forNewProduct($product, $command->name, $command->sku, $command->slug, $resolvedVariantGroup, $command->stock, $command->variantDefinitions);
            });
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de créer le produit.', $exception);
        }

        return $product;
    }

    public function update(ProductWriteCommand $command): Product
    {
        $product = $command->product;
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        $normalizedSku = strtoupper($command->sku);
        $resolvedVariantGroup = $this->variants->resolveVariantGroup($command->variantGroup ?? $product->getVariantGroup(), $command->name, []);

        $this->rules->assertValidData($command->name, $normalizedSku, $command->description, $command->shortDescription, $command->priceCents, $command->stock);
        $this->rules->assertUniqueness($normalizedSku, $product->getId());
        $this->variants->assertDefinitionsAreUnique($resolvedVariantGroup, $product, $command->color, $command->storageCapacity, $command->variantDefinitions);

        $this->attributes->update(
            $product,
            $command->name,
            $this->rules->resolveSlug($command->slug, $command->name, $product->getId()),
            $normalizedSku,
            $command->description,
            $command->shortDescription,
            $command->priceCents,
            $command->stock,
            $command->isPublished,
            $command->isFeaturedHome,
            $command->category,
            $command->imageAlt,
            $command->sellingType,
            $command->brand,
            $resolvedVariantGroup,
            $command->releaseYear,
            $command->storageCapacity,
            $command->memoryRam,
            $command->color,
        );
        $this->discounts->applyOnUpdate($product, $command->discountEnabled, $command->discountType, $command->discountValue, $command->discountStartsAt, $command->discountEndsAt);

        $galleryToRemove = $command->galleryToRemove;
        if ($command->removeImage) {
            $galleryToRemove[] = 0;
        }

        try {
            $this->transactions->transactional(function () use ($product, $command, $resolvedVariantGroup, $galleryToRemove): void {
                $this->gallery->update($product, $command->galleryFiles, $galleryToRemove);
                $this->variantBatch->forExistingProduct($product, $command->name, $command->sku, $command->slug, $resolvedVariantGroup, $command->stock, $command->variantDefinitions);
            });
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour le produit.', $exception);
        }

        return $product;
    }

    public function delete(Product $product): void
    {
        try {
            $this->transactions->transactional(function () use ($product): void {
                $this->persistence->remove($product);
            });
            $this->catalogCache->clear();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer le produit.', $exception);
        }
    }
}
