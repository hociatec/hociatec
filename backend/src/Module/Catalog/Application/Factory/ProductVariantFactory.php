<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Factory;

use App\Module\Catalog\Application\Calculator\ProductCatalogRules;
use App\Module\Catalog\Application\DTO\ProductVariantCopyData;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantFactory
{
    public function __construct(
        private ProductCatalogRepository $productRepository,
        private ProductCatalogRules $rules,
    ) {
    }

    public function createVariantCopy(ProductVariantCopyData $data): Product
    {
        $variantProduct = new Product(
            $this->buildVariantName($data->baseName, $data->color, $data->storageCapacity),
            $this->buildVariantSlug($data->baseSlug ?? $data->baseName, $data->color, $data->storageCapacity, $data->position),
            $this->buildVariantSku($data->baseSku, $data->color, $data->storageCapacity, $data->position),
            $data->template->getDescription(),
            $data->template->getPriceCents(),
            $data->stock,
            $data->template->getCategory(),
        );

        return $variantProduct
            ->setShortDescription($data->template->getShortDescription())
            ->setIsPublished($data->template->isPublished())
            ->setIsFeaturedHome($data->template->isFeaturedHome())
            ->setImageAlt($data->template->getImageAlt())
            ->setBrandReference($data->template->getBrandReference())
            ->setVariantGroup($data->variantGroup)
            ->setVariantPosition($data->position)
            ->setReleaseYear($data->template->getReleaseYear())
            ->setStorageCapacity($data->storageCapacity)
            ->setMemoryRam($data->template->getMemoryRam())
            ->setColor($data->color)
            ->setSellingType($data->template->getSellingType())
            ->setDiscountEnabled($data->template->isDiscountEnabled())
            ->setDiscountType($data->template->getDiscountType())
            ->setDiscountValue($data->template->getDiscountValue())
            ->setDiscountStartsAt($data->template->getDiscountStartsAt())
            ->setDiscountEndsAt($data->template->getDiscountEndsAt())
            ->setImageName($data->template->getImageName())
            ->setImageSize($data->template->getImageSize())
            ->setGalleryImage2Name($data->template->getGalleryImage2Name())
            ->setGalleryImage2Size($data->template->getGalleryImage2Size())
            ->setGalleryImage3Name($data->template->getGalleryImage3Name())
            ->setGalleryImage3Size($data->template->getGalleryImage3Size())
            ->setGalleryImage4Name($data->template->getGalleryImage4Name())
            ->setGalleryImage4Size($data->template->getGalleryImage4Size());
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
