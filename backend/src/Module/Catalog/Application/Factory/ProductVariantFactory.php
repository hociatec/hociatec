<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Factory;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Application\Service\ProductCatalogRules;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantFactory
{
    public function __construct(
        private ProductCatalogRepository $productRepository,
        private ProductCatalogRules $rules,
    ) {
    }

    public function createVariantCopy(
        Product $template,
        string $baseName,
        string $baseSku,
        ?string $baseSlug,
        string $variantGroup,
        ?string $color,
        ?string $storageCapacity,
        int $stock,
        int $index,
    ): Product {
        $variantProduct = new Product(
            $this->buildVariantName($baseName, $color, $storageCapacity),
            $this->buildVariantSlug($baseSlug ?? $baseName, $color, $storageCapacity, $index),
            $this->buildVariantSku($baseSku, $color, $storageCapacity, $index),
            $template->getDescription(),
            $template->getPriceCents(),
            $stock,
            $template->getCategory(),
        );

        return $variantProduct
            ->setShortDescription($template->getShortDescription())
            ->setIsPublished($template->isPublished())
            ->setIsFeaturedHome($template->isFeaturedHome())
            ->setImageAlt($template->getImageAlt())
            ->setBrandReference($template->getBrandReference())
            ->setVariantGroup($variantGroup)
            ->setVariantPosition($index)
            ->setReleaseYear($template->getReleaseYear())
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($template->getMemoryRam())
            ->setColor($color)
            ->setSellingType($template->getSellingType())
            ->setDiscountEnabled($template->isDiscountEnabled())
            ->setDiscountType($template->getDiscountType())
            ->setDiscountValue($template->getDiscountValue())
            ->setDiscountStartsAt($template->getDiscountStartsAt())
            ->setDiscountEndsAt($template->getDiscountEndsAt())
            ->setImageName($template->getImageName())
            ->setImageSize($template->getImageSize())
            ->setGalleryImage2Name($template->getGalleryImage2Name())
            ->setGalleryImage2Size($template->getGalleryImage2Size())
            ->setGalleryImage3Name($template->getGalleryImage3Name())
            ->setGalleryImage3Size($template->getGalleryImage3Size())
            ->setGalleryImage4Name($template->getGalleryImage4Name())
            ->setGalleryImage4Size($template->getGalleryImage4Size());
    }

    private function buildVariantName(string $baseName, ?string $color, ?string $storageCapacity): string
    {
        $parts = array_values(array_filter([
            null !== $color ? trim($color) : '',
            null !== $storageCapacity ? trim($storageCapacity) : '',
        ]));

        return [] === $parts ? $baseName : sprintf('%s (%s)', $baseName, implode(') (', $parts));
    }

    private function buildVariantSku(string $baseSku, ?string $color, ?string $storageCapacity, int $index): string
    {
        $suffix = $this->rules->slugify(trim((string) $color.' '.(string) $storageCapacity));
        if ('produit' === $suffix) {
            $suffix = (string) ($index + 1);
        }

        return strtoupper(sprintf('%s-%s', $baseSku, $suffix));
    }

    private function buildVariantSlug(string $baseSlugOrName, ?string $color, ?string $storageCapacity, int $index): string
    {
        $base = $this->rules->slugify($baseSlugOrName);
        $suffix = $this->rules->slugify(trim((string) $color.' '.(string) $storageCapacity));

        if ('produit' === $suffix) {
            $suffix = (string) ($index + 1);
        }

        $candidate = sprintf('%s-%s', $base, $suffix);
        $attempt = 1;

        while ($this->productRepository->existsWithSlug($candidate, null)) {
            ++$attempt;
            $candidate = sprintf('%s-%s-%d', $base, $suffix, $attempt);
        }

        return $candidate;
    }
}
