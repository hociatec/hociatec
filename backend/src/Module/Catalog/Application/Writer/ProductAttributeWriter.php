<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Writer;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;

final class ProductAttributeWriter
{
    public function create(
        string $name,
        string $slug,
        string $sku,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
        bool $isPublished,
        bool $isFeaturedHome,
        Category $category,
        ?string $imageAlt,
        ?string $sellingType,
        ?Brand $brand,
        string $variantGroup,
        ?int $releaseYear,
        ?string $storageCapacity,
        ?string $memoryRam,
        ?string $color,
    ): Product {
        $product = new Product($name, $slug, $sku, $description, $priceCents, $stock, $category);

        return $this->applySharedFields(
            $product,
            $shortDescription,
            $isPublished,
            $isFeaturedHome,
            $imageAlt,
            $sellingType,
            $brand,
            $variantGroup,
            $releaseYear,
            $storageCapacity,
            $memoryRam,
            $color,
        )->setVariantPosition(1);
    }

    public function update(
        Product $product,
        string $name,
        string $slug,
        string $sku,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
        bool $isPublished,
        bool $isFeaturedHome,
        Category $category,
        ?string $imageAlt,
        ?string $sellingType,
        ?Brand $brand,
        string $variantGroup,
        ?int $releaseYear,
        ?string $storageCapacity,
        ?string $memoryRam,
        ?string $color,
    ): Product {
        $product
            ->setName($name)
            ->setSlug($slug)
            ->setSku($sku)
            ->setDescription($description)
            ->setPriceCents($priceCents)
            ->setStock($stock)
            ->setCategory($category);

        return $this->applySharedFields(
            $product,
            $shortDescription,
            $isPublished,
            $isFeaturedHome,
            $imageAlt,
            $sellingType,
            $brand,
            $variantGroup,
            $releaseYear,
            $storageCapacity,
            $memoryRam,
            $color,
        )->setVariantPosition($product->getVariantPosition() > 0 ? $product->getVariantPosition() : 1);
    }

    private function applySharedFields(
        Product $product,
        ?string $shortDescription,
        bool $isPublished,
        bool $isFeaturedHome,
        ?string $imageAlt,
        ?string $sellingType,
        ?Brand $brand,
        string $variantGroup,
        ?int $releaseYear,
        ?string $storageCapacity,
        ?string $memoryRam,
        ?string $color,
    ): Product {
        $product
            ->setShortDescription($shortDescription)
            ->setIsPublished($isPublished)
            ->setIsFeaturedHome($isFeaturedHome)
            ->setImageAlt($imageAlt)
            ->setBrandReference($brand)
            ->setVariantGroup($variantGroup)
            ->setReleaseYear($releaseYear)
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($memoryRam)
            ->setColor($color);

        if (null !== $sellingType) {
            $product->setSellingType($sellingType);
        }

        return $product;
    }
}
