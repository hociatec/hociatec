<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\ValueObject\ProductDiscount;

final class CatalogFormatter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatCategory(Category $category, bool $includeCounts = false): array
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
    public static function formatBrand(Brand $brand, ?int $productsCount = null): array
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
    public static function formatProduct(Product $product, bool $includePrivateFields = false): array
    {
        $gallery = self::formatGallery($product);

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
            'imageUrl' => $gallery[0]['url'] ?? self::resolveImageUrlFromName($product->getImageName()),
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
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    public static function formatProductProjection(array $product): array
    {
        $name = (string) $product['name'];
        $imageAlt = null !== $product['imageAlt'] ? (string) $product['imageAlt'] : null;
        $gallery = self::formatProjectedGallery($product, $imageAlt ?? $name);
        $priceCents = (int) $product['priceCents'];
        $discount = new ProductDiscount(
            (bool) $product['discountEnabled'],
            null !== $product['discountType'] ? (string) $product['discountType'] : null,
            null !== $product['discountValue'] ? (int) $product['discountValue'] : null,
            $product['discountStartsAt'] instanceof \DateTimeImmutable ? $product['discountStartsAt'] : null,
            $product['discountEndsAt'] instanceof \DateTimeImmutable ? $product['discountEndsAt'] : null,
        );

        $data = [
            'id' => (int) $product['id'],
            'name' => $name,
            'slug' => (string) $product['slug'],
            'sku' => (string) $product['sku'],
            'shortDescription' => null !== $product['shortDescription'] ? (string) $product['shortDescription'] : null,
            'description' => (string) $product['description'],
            'priceCents' => $priceCents,
            'sellingType' => (string) $product['sellingType'],
            'sellingTypeLabel' => 'rental' === $product['sellingType'] ? 'Location' : 'Vente',
            'priceUnitLabel' => 'rental' === $product['sellingType'] ? '/ mois' : null,
            'brand' => null !== $product['brand'] ? (string) $product['brand'] : null,
            'brandId' => null !== $product['brandId'] ? (int) $product['brandId'] : null,
            'variantGroup' => null !== $product['variantGroup'] ? (string) $product['variantGroup'] : null,
            'variantPosition' => (int) $product['variantPosition'],
            'releaseYear' => null !== $product['releaseYear'] ? (int) $product['releaseYear'] : null,
            'storageCapacity' => null !== $product['storageCapacity'] ? (string) $product['storageCapacity'] : null,
            'memoryRam' => null !== $product['memoryRam'] ? (string) $product['memoryRam'] : null,
            'color' => null !== $product['color'] ? (string) $product['color'] : null,
            'effectivePriceCents' => $discount->effectivePriceCents($priceCents),
            'stock' => (int) $product['stock'],
            'isPublished' => (bool) $product['isPublished'],
            'isFeaturedHome' => (bool) $product['isFeaturedHome'],
            'imageUrl' => $gallery[0]['url'] ?? self::resolveImageUrlFromName(null !== $product['imageName'] ? (string) $product['imageName'] : null),
            'imageAlt' => $imageAlt,
            'createdAt' => $product['createdAt'] instanceof \DateTimeInterface ? $product['createdAt']->format(DATE_ATOM) : null,
            'updatedAt' => $product['updatedAt'] instanceof \DateTimeInterface ? $product['updatedAt']->format(DATE_ATOM) : null,
            'category' => [
                'id' => (int) $product['categoryId'],
                'name' => (string) $product['categoryName'],
                'slug' => (string) $product['categorySlug'],
            ],
            'reviews' => [
                'count' => (int) $product['reviewsCount'],
                'average' => (float) $product['reviewsAverage'],
            ],
            'gallery' => $gallery,
        ];

        if ($discount->isEnabled() && null !== $discount->type() && null !== $discount->value()) {
            $data['discount'] = [
                'type' => $discount->type(),
                'value' => $discount->value(),
                'startsAt' => $discount->startsAt()?->format(DATE_ATOM),
                'endsAt' => $discount->endsAt()?->format(DATE_ATOM),
                'active' => $discount->effectivePriceCents($priceCents) < $priceCents,
            ];
        }

        return $data;
    }

    /**
     * @return list<array{position:int,url:string,alt:string,isPrimary:bool}>
     */
    private static function formatGallery(Product $product): array
    {
        $items = [];

        foreach ([0, 1, 2, 3] as $position) {
            $fileName = $product->getGalleryImageNameByPosition($position);

            if (null === $fileName) {
                continue;
            }

            $url = self::resolveImageUrlFromName($fileName);

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

    /**
     * @param array<string, mixed> $product
     *
     * @return list<array{position:int,url:string,alt:string,isPrimary:bool}>
     */
    private static function formatProjectedGallery(array $product, string $alt): array
    {
        $items = [];
        foreach ([0 => 'imageName', 1 => 'galleryImage2Name', 2 => 'galleryImage3Name', 3 => 'galleryImage4Name'] as $position => $key) {
            $fileName = null !== $product[$key] ? (string) $product[$key] : null;
            $url = self::resolveImageUrlFromName($fileName);
            if (null === $url) {
                continue;
            }

            $items[] = [
                'position' => $position,
                'url' => $url,
                'alt' => $alt,
                'isPrimary' => 0 === $position,
            ];
        }

        return $items;
    }

    private static function resolveImageUrlFromName(?string $fileName): ?string
    {
        if (null === $fileName || '' === $fileName) {
            return null;
        }

        return sprintf('/uploads/products/%s', ltrim($fileName, '/'));
    }
}
