<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;

final class CatalogFormatter
{
    public function __construct(private readonly ?CatalogProductMediaProjection $media = null)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCategory(Category $category, int|bool|null $productsCount = null): array
    {
        if (true === $productsCount) {
            $productsCount = $category->getProducts()->count();
        }

        if (false === $productsCount) {
            $productsCount = null;
        }

        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'isVisible' => $category->isVisible(),
            'attributeDefinitions' => $category->getAttributeDefinitions(),
            'createdAt' => $category->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $category->getUpdatedAt()->format(DATE_ATOM),
        ];

        if (null !== $productsCount) {
            $data['productsCount'] = $productsCount;
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
    public function formatProduct(Product $product, bool $includePrivateFields = false, ?string $preferredSellingType = null): array
    {
        $gallery = ($this->media ?? new CatalogProductMediaProjection())->formatEntityGallery($product);
        $sellingContext = $product->getSellingTypeContext($preferredSellingType);

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'sku' => $product->getSku(),
            'shortDescription' => $product->getShortDescription(),
            'description' => $product->getDescription(),
            'priceCents' => $sellingContext['priceCents'],
            'sellingType' => $sellingContext['sellingType'],
            'sellingTypeLabel' => $sellingContext['sellingTypeLabel'],
            'priceUnitLabel' => $sellingContext['priceUnitLabel'],
            'availableForSale' => $product->isAvailableForSale(),
            'availableForRental' => $product->isAvailableForRental(),
            'availableModes' => $product->getAvailableSellingTypes(),
            'salePriceCents' => $product->getSalePriceCents(),
            'rentalPriceCents' => $product->getRentalPriceCents(),
            'brand' => $product->getBrand(),
            'brandId' => $product->getBrandId(),
            'variantGroup' => $product->getVariantGroup(),
            'variantPosition' => $product->getVariantPosition(),
            'releaseYear' => $product->getReleaseYear(),
            'attributes' => $product->getAttributes(),
            'storageCapacity' => $product->getStorageCapacity(),
            'memoryRam' => $product->getMemoryRam(),
            'color' => $product->getColor(),
            'effectivePriceCents' => $product->getDisplayEffectivePriceCents($preferredSellingType),
            'stock' => $product->getStock(),
            'isPublished' => $product->isPublished(),
            'isFeaturedHome' => $product->isFeaturedHome(),
            'imageUrl' => $gallery[0]['url'] ?? $product->getImageExternalUrl() ?? ($this->media ?? new CatalogProductMediaProjection())->resolveImageUrlFromName($product->getImageName()),
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
                'active' => $product->getDisplayEffectivePriceCents($preferredSellingType) < $sellingContext['priceCents'],
            ];
        }

        return $data;
    }
}
