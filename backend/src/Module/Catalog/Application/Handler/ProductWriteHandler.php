<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\Calculator\ProductCatalogRules;
use App\Module\Catalog\Application\Cache\CatalogCacheInvalidator;
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
        private CatalogCacheInvalidator $cacheInvalidator,
        private ProductAttributeWriter $attributes = new ProductAttributeWriter(),
    ) {
    }

    public function create(ProductWriteCommand $command): Product
    {
        $normalizedSku = strtoupper($command->core->sku);
        $resolvedVariantGroup = $this->variants->resolveVariantGroup($command->variant->group, $command->core->name, $command->variant->definitions);

        $this->rules->assertValidData($command->core->name, $normalizedSku, $command->core->description, $command->core->shortDescription, $command->core->priceCents, $command->core->stock);
        $this->rules->assertUniqueness($normalizedSku, null);
        $this->variants->assertDefinitionsAreUnique($resolvedVariantGroup, null, $command->variant->color, $command->variant->storageCapacity, $command->variant->definitions);

        $product = $this->attributes->create(
            $command,
            $this->rules->resolveSlug($command->core->slug, $command->core->name, null),
            $normalizedSku,
            $resolvedVariantGroup,
        );

        $this->discounts->applyOnCreate($product, $command->discount->enabled, $command->discount->type, $command->discount->value, $command->discount->startsAt, $command->discount->endsAt);

        try {
            $this->transactions->transactional(function () use ($product, $command, $resolvedVariantGroup): void {
                $this->gallery->stage($product, $command->gallery->files, []);
                $this->persistence->persist($product);
                $this->variantBatch->forNewProduct($product, $command->core->name, $command->core->sku, $command->core->slug, $resolvedVariantGroup, $command->core->stock, $command->variant->definitions);
            });
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de créer le produit.', $exception);
        }
        $this->cacheInvalidator->invalidateAfterWrite('create');

        return $product;
    }

    public function update(ProductWriteCommand $command): Product
    {
        $product = $command->product;
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        $normalizedSku = strtoupper($command->core->sku);
        $resolvedVariantGroup = $this->variants->resolveVariantGroup($command->variant->group ?? $product->getVariantGroup(), $command->core->name, []);

        $this->rules->assertValidData($command->core->name, $normalizedSku, $command->core->description, $command->core->shortDescription, $command->core->priceCents, $command->core->stock);
        $this->rules->assertUniqueness($normalizedSku, $product->getId());
        $this->variants->assertDefinitionsAreUnique($resolvedVariantGroup, $product, $command->variant->color, $command->variant->storageCapacity, $command->variant->definitions);

        $this->attributes->update(
            $product,
            $command,
            $this->rules->resolveSlug($command->core->slug, $command->core->name, $product->getId()),
            $normalizedSku,
            $resolvedVariantGroup,
        );
        $this->discounts->applyOnUpdate($product, $command->discount->enabled, $command->discount->type, $command->discount->value, $command->discount->startsAt, $command->discount->endsAt);

        $galleryToRemove = $command->gallery->toRemove;
        if ($command->gallery->removeMainImage) {
            $galleryToRemove[] = 0;
        }

        try {
            $this->transactions->transactional(function () use ($product, $command, $resolvedVariantGroup, $galleryToRemove): void {
                $this->gallery->stage($product, $command->gallery->files, $galleryToRemove);
                $this->variantBatch->forExistingProduct($product, $command->core->name, $command->core->sku, $command->core->slug, $resolvedVariantGroup, $command->core->stock, $command->variant->definitions);
            });
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour le produit.', $exception);
        }
        $this->cacheInvalidator->invalidateAfterWrite('update');

        return $product;
    }

    public function delete(Product $product): void
    {
        try {
            $this->transactions->transactional(function () use ($product): void {
                $this->persistence->remove($product);
            });
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer le produit.', $exception);
        }
        $this->cacheInvalidator->invalidateAfterWrite('delete');
    }
}
