<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;

final readonly class ProductWriteHandler
{
    public function __construct(
        private ProductWriteExecution $execution,
        private ProductWriteValidator $validator,
        private ProductWriteGalleryPlan $galleryPlan,
        private ProductWriteServices $services,
    ) {
    }

    public function create(ProductWriteCommand $command): Product
    {
        $normalizedSku = $this->validator->normalizedSku($command);
        $resolvedVariantGroup = $this->services->variants->resolveVariantGroup($command->variant->group, $command->core->name, $command->variant->definitions);

        $this->validator->validateCreate($command, $normalizedSku, $resolvedVariantGroup);

        $product = $this->services->attributes->create(
            $command,
            $this->validator->resolveSlug($command, null),
            $normalizedSku,
            $resolvedVariantGroup,
        );

        $this->services->discounts->applyOnCreate($product, $command->discount->enabled, $command->discount->type, $command->discount->value, $command->discount->startsAt, $command->discount->endsAt);

        try {
            $this->execution->transactions->transactional(function () use ($product, $command, $resolvedVariantGroup): void {
                $this->services->gallery->stage($product, $command->gallery->files, []);
                $this->execution->persistence->persist($product);
                $this->services->variantBatch->forNewProduct($product, $command->core->name, $command->core->sku, $command->core->slug, $resolvedVariantGroup, $command->core->stock, $command->variant->definitions);
            });
        } catch (\RuntimeException|DBALException|ORMException $exception) {
            throw CatalogOperationException::failed('Impossible de créer le produit.', $exception);
        }
        $this->execution->cache->invalidateAfterWrite('create');

        return $product;
    }

    public function update(ProductWriteCommand $command): Product
    {
        $product = $command->product;
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        $normalizedSku = $this->validator->normalizedSku($command);
        $resolvedVariantGroup = $this->services->variants->resolveVariantGroup($command->variant->group ?? $product->getVariantGroup(), $command->core->name, []);

        $this->validator->validateUpdate($command, $product, $normalizedSku, $resolvedVariantGroup);

        $this->services->attributes->update(
            $product,
            $command,
            $this->validator->resolveSlug($command, $product->getId()),
            $normalizedSku,
            $resolvedVariantGroup,
        );
        $this->services->discounts->applyOnUpdate($product, $command->discount->enabled, $command->discount->type, $command->discount->value, $command->discount->startsAt, $command->discount->endsAt);

        $galleryToRemove = $this->galleryPlan->removals($command->gallery);

        try {
            $this->execution->transactions->transactional(function () use ($product, $command, $resolvedVariantGroup, $galleryToRemove): void {
                $this->services->gallery->stage($product, $command->gallery->files, $galleryToRemove);
                $this->services->variantBatch->forExistingProduct($product, $command->core->name, $command->core->sku, $command->core->slug, $resolvedVariantGroup, $command->core->stock, $command->variant->definitions);
            });
        } catch (\RuntimeException|DBALException|ORMException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour le produit.', $exception);
        }
        $this->execution->cache->invalidateAfterWrite('update');

        return $product;
    }

    public function delete(Product $product): void
    {
        try {
            $this->execution->transactions->transactional(function () use ($product): void {
                $this->execution->persistence->remove($product);
            });
        } catch (\RuntimeException|DBALException|ORMException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer le produit.', $exception);
        }
        $this->execution->cache->invalidateAfterWrite('delete');
    }
}
