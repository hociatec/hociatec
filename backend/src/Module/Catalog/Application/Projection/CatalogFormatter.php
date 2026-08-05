<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;

final class CatalogFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function formatCategory(Category $category, bool $includeCounts = false): array
    {
        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'isVisible' => $category->isVisible(),
            'createdAt' => $category->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $category->getUpdatedAt()->format(DATE_ATOM),
        ];

        if ($includeCounts) {
            $data['productsCount'] = $category->getProducts()->count();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatBrand(Brand $brand, ?int $productsCount = null): array
    {
        $data = [
            'id' => $brand->getId(),
            'name' => $brand->getName(),
            'createdAt' => $brand->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $brand->getUpdatedAt()->format(DATE_ATOM),
        ];

        if (null !== $productsCount) {
            $data['productsCount'] = $productsCount;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatProduct(Product $product, bool $includePrivateFields = false): array
    {
        $gallery = $this->formatGallery($product);

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'sku' => $product->getSku(),
            'shortDescription' => $product->getShortDescription(),
            'description' => $product->getDescription(),
            'priceCents' => $product->getPriceCents(),
            'sellingType' => $product->getSellingType(),
            'sellingTypeLabel' => 'rental' === $product->getSellingType() ? 'Location' : 'Vente',
            'priceUnitLabel' => 'rental' === $product->getSellingType() ? '/ mois' : null,
            'brand' => $product->getBrand(),
            'brandId' => $product->getBrandId(),
            'variantGroup' => $product->getVariantGroup(),
            'variantPosition' => $product->getVariantPosition(),
            'releaseYear' => $product->getReleaseYear(),
            'storageCapacity' => $product->getStorageCapacity(),
            'memoryRam' => $product->getMemoryRam(),
            'color' => $product->getColor(),
            'effectivePriceCents' => $product->getEffectivePriceCents(),
            'stock' => $product->getStock(),
            'isPublished' => $product->isPublished(),
            'isFeaturedHome' => $product->isFeaturedHome(),
            'imageUrl' => $gallery[0]['url'] ?? $product->getImageExternalUrl() ?? $this->resolveImageUrlFromName($product->getImageName()),
            'imageAlt' => $product->getImageAlt(),
            'createdAt' => $product->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $product->getUpdatedAt()->format(DATE_ATOM),
            'category' => [
                'id' => $product->getCategory()->getId(),
                'name' => $product->getCategory()->getName(),
                'slug' => $product->getCategory()->getSlug(),
            ],
            'reviews' => [
                'count' => $product->getReviewsCount(),
                'average' => $product->getReviewsAverage(),
            ],
            'gallery' => $gallery,
        ];

        if ($includePrivateFields) {
            $data['imageName'] = $product->getImageName();
            $data['imageSize'] = $product->getImageSize();
            $data['imageExternalUrl'] = $product->getImageExternalUrl();
            $data['galleryMeta'] = array_map(
                static fn (int $position) => [
                    'position' => $position,
                    'name' => $product->getGalleryImageNameByPosition($position),
                ],
                [0, 1, 2, 3],
            );
        }

        if ($product->isDiscountEnabled() && null !== $product->getDiscountType() && null !== $product->getDiscountValue()) {
            $data['discount'] = [
                'type' => $product->getDiscountType(),
                'value' => $product->getDiscountValue(),
                'startsAt' => $product->getDiscountStartsAt()?->format(DATE_ATOM),
                'endsAt' => $product->getDiscountEndsAt()?->format(DATE_ATOM),
                'active' => $product->getEffectivePriceCents() < $product->getPriceCents(),
            ];
        }

        return $data;
    }

    /**
     * @return list<array{position:int,url:string,alt:string,isPrimary:bool}>
     */
    private function formatGallery(Product $product): array
    {
        $items = [];

        $hasExternalImage = null !== $product->getImageExternalUrl() && '' !== trim($product->getImageExternalUrl());

        if ($hasExternalImage) {
            $items[] = [
                'position' => 0,
                'url' => $product->getImageExternalUrl(),
                'alt' => $product->getImageAlt() ?? $product->getName(),
                'isPrimary' => true,
            ];
        }

        foreach ($hasExternalImage ? [1, 2, 3] : [0, 1, 2, 3] as $position) {
            $fileName = $product->getGalleryImageNameByPosition($position);

            if (null === $fileName) {
                continue;
            }

            $url = $this->resolveImageUrlFromName($fileName);

            if (null === $url) {
                continue;
            }

            $items[] = [
                'position' => $position,
                'url' => $url,
                'alt' => $product->getImageAlt() ?? $product->getName(),
                'isPrimary' => 0 === $position,
            ];
        }

        return $items;
    }

    private function resolveImageUrlFromName(?string $fileName): ?string
    {
        if (null === $fileName || '' === $fileName) {
            return null;
        }

        return sprintf('/uploads/products/%s', ltrim($fileName, '/'));
    }
}
