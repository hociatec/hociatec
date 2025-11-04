<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;

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

        // Attach discount metadata if configured
        if ($product->isDiscountEnabled() && $product->getDiscountType() !== null && $product->getDiscountValue() !== null) {
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
    private static function formatGallery(Product $product): array
    {
        $items = [];

        foreach ([0, 1, 2, 3] as $position) {
            $fileName = $product->getGalleryImageNameByPosition($position);

            if ($fileName === null) {
                continue;
            }

            $url = self::resolveImageUrlFromName($fileName);

            if ($url === null) {
                continue;
            }

            $items[] = [
                'position' => $position,
                'url' => $url,
                'alt' => $product->getImageAlt() ?? $product->getName(),
                'isPrimary' => $position === 0,
            ];
        }

        return $items;
    }

    private static function resolveImageUrlFromName(?string $fileName): ?string
    {
        if ($fileName === null || $fileName === '') {
            return null;
        }

        return sprintf('/uploads/products/%s', ltrim($fileName, '/'));
    }
}
