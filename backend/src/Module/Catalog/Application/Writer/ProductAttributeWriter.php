<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Writer;

use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Domain\Entity\Product;

final class ProductAttributeWriter
{
    public function create(
        ProductWriteCommand $command,
        string $slug,
        string $sku,
        string $variantGroup,
    ): Product {
        $product = new Product(
            $command->core->name,
            $slug,
            $sku,
            $command->core->description,
            $command->core->priceCents,
            $command->core->stock,
            $command->core->category,
        );

        return $this->applySharedFields($product, $command, $variantGroup)->setVariantPosition(1);
    }

    public function update(
        Product $product,
        ProductWriteCommand $command,
        string $slug,
        string $sku,
        string $variantGroup,
    ): Product {
        $product
            ->setName($command->core->name)
            ->setSlug($slug)
            ->setSku($sku)
            ->setDescription($command->core->description)
            ->setPriceCents($command->core->priceCents)
            ->setStock($command->core->stock)
            ->setCategory($command->core->category);

        return $this->applySharedFields($product, $command, $variantGroup)
            ->setVariantPosition($product->getVariantPosition() > 0 ? $product->getVariantPosition() : 1);
    }

    private function applySharedFields(
        Product $product,
        ProductWriteCommand $command,
        string $variantGroup,
    ): Product {
        $product
            ->setShortDescription($command->core->shortDescription)
            ->setIsPublished($command->core->isPublished)
            ->setIsFeaturedHome($command->core->isFeaturedHome)
            ->setImageAlt($command->core->imageAlt)
            ->setBrandReference($command->core->brand)
            ->setVariantGroup($variantGroup)
            ->setReleaseYear($command->variant->releaseYear)
            ->setStorageCapacity($command->variant->storageCapacity)
            ->setMemoryRam($command->variant->memoryRam)
            ->setColor($command->variant->color);

        $product->setSellingType($command->core->sellingType);

        return $product;
    }
}
