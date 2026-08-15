<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\ProductSellingType;
use App\Module\Catalog\Domain\ValueObject\ProductDiscount;

final class ProductCatalogListProjectionFormatter
{
    public function __construct(
        private readonly ?CatalogProductMediaProjection $media = null,
        private readonly ?ProductCatalogProjectionSupport $support = null,
    ) {
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    public function format(array $product, ?string $preferredSellingType = null): array
    {
        $support = $this->support ?? new ProductCatalogProjectionSupport();
        $media = $this->media ?? new CatalogProductMediaProjection();
        $name = (string) $product['name'];
        $sellingType = $support->resolveSellingType($product, $preferredSellingType);
        $availableForSale = $support->supportsSellingType($product, ProductSellingType::Sale->value);
        $availableForRental = $support->supportsSellingType($product, ProductSellingType::Rental->value);
        $imageAlt = null !== $product['imageAlt'] ? (string) $product['imageAlt'] : null;
        $gallery = $media->formatProjectionGallery($product, $imageAlt ?? $name);
        $priceCents = $support->resolvePriceCents($product, $sellingType);
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
            'modelName' => null !== ($product['modelName'] ?? null) ? (string) $product['modelName'] : null,
            'slug' => (string) $product['slug'],
            'sku' => (string) $product['sku'],
            'shortDescription' => null !== $product['shortDescription'] ? (string) $product['shortDescription'] : null,
            'description' => (string) $product['description'],
            'priceCents' => $priceCents,
            'sellingType' => $sellingType,
            'sellingTypeLabel' => ProductSellingType::label($sellingType),
            'priceUnitLabel' => ProductSellingType::priceUnitLabel($sellingType),
            'availableForSale' => $availableForSale,
            'availableForRental' => $availableForRental,
            'availableModes' => $support->availableModes($product),
            'salePriceCents' => isset($product['salePriceCents']) ? (null !== $product['salePriceCents'] ? (int) $product['salePriceCents'] : null) : null,
            'rentalPriceCents' => isset($product['rentalPriceCents']) ? (null !== $product['rentalPriceCents'] ? (int) $product['rentalPriceCents'] : null) : null,
            'brand' => null !== $product['brand'] ? (string) $product['brand'] : null,
            'brandId' => null !== $product['brandId'] ? (int) $product['brandId'] : null,
            'variantGroup' => null !== $product['variantGroup'] ? (string) $product['variantGroup'] : null,
            'variantPosition' => (int) $product['variantPosition'],
            'variantsCount' => isset($product['variantsCount']) ? (int) $product['variantsCount'] : null,
            'totalStock' => isset($product['totalStock']) ? (int) $product['totalStock'] : null,
            'variantColors' => isset($product['variantColors']) && is_array($product['variantColors']) ? array_values(array_map('strval', $product['variantColors'])) : null,
            'variantStorages' => isset($product['variantStorages']) && is_array($product['variantStorages']) ? array_values(array_map('strval', $product['variantStorages'])) : null,
            'variantMemoryRams' => isset($product['variantMemoryRams']) && is_array($product['variantMemoryRams']) ? array_values(array_map('strval', $product['variantMemoryRams'])) : null,
            'variantAttributes' => isset($product['variantAttributes']) && is_array($product['variantAttributes']) ? $product['variantAttributes'] : null,
            'minVariantPriceCents' => isset($product['minVariantPriceCents']) ? (int) $product['minVariantPriceCents'] : null,
            'maxVariantPriceCents' => isset($product['maxVariantPriceCents']) ? (int) $product['maxVariantPriceCents'] : null,
            'minVariantEffectivePriceCents' => isset($product['minVariantEffectivePriceCents']) ? (int) $product['minVariantEffectivePriceCents'] : null,
            'maxVariantEffectivePriceCents' => isset($product['maxVariantEffectivePriceCents']) ? (int) $product['maxVariantEffectivePriceCents'] : null,
            'releaseYear' => null !== $product['releaseYear'] ? (int) $product['releaseYear'] : null,
            'attributes' => $support->normalizeAttributes($product['attributes'] ?? null),
            'storageCapacity' => $support->attributeValue($product['attributes'] ?? null, 'storage'),
            'memoryRam' => $support->attributeValue($product['attributes'] ?? null, 'ram'),
            'color' => $support->attributeValue($product['attributes'] ?? null, 'color'),
            'effectivePriceCents' => $discount->effectivePriceCents($priceCents),
            'stock' => (int) $product['stock'],
            'isPublished' => (bool) $product['isPublished'],
            'isFeaturedHome' => (bool) $product['isFeaturedHome'],
            'imageUrl' => $gallery[0]['url'] ?? $media->resolveExternalImageUrl($product) ?? $media->resolveImageUrlFromName(null !== $product['imageName'] ? (string) $product['imageName'] : null),
            'imageAlt' => $imageAlt,
            'createdAt' => $this->normalizeDateValue($product['createdAt'] ?? null),
            'updatedAt' => $this->normalizeDateValue($product['updatedAt'] ?? null),
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

        if ($discount->enabled && null !== $discount->type && null !== $discount->value) {
            $data['discount'] = [
                'type' => $discount->type,
                'value' => $discount->value,
                'startsAt' => $discount->startsAt?->format(DATE_ATOM),
                'endsAt' => $discount->endsAt?->format(DATE_ATOM),
                'active' => $discount->effectivePriceCents($priceCents) < $priceCents,
            ];
        }

        return $data;
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value)) {
            $normalized = trim($value);

            return '' !== $normalized ? $normalized : null;
        }

        return null;
    }
}
