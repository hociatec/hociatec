<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\ProductSellingType;
use App\Module\Catalog\Domain\ValueObject\ProductDiscount;

final class ProductCatalogListProjectionFormatter
{
    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    public function format(array $product): array
    {
        $name = (string) $product['name'];
        $sellingType = $this->normalizeSellingType($product['sellingType']);
        $imageAlt = null !== $product['imageAlt'] ? (string) $product['imageAlt'] : null;
        $gallery = $this->formatGallery($product, $imageAlt ?? $name);
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
            'sellingType' => $sellingType,
            'sellingTypeLabel' => 'rental' === $sellingType ? 'Location' : 'Vente',
            'priceUnitLabel' => 'rental' === $sellingType ? '/ mois' : null,
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
            'imageUrl' => $gallery[0]['url'] ?? $this->resolveExternalImageUrl($product) ?? $this->resolveImageUrlFromName(null !== $product['imageName'] ? (string) $product['imageName'] : null),
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

    /**
     * @param array<string, mixed> $product
     *
     * @return list<array{position:int,url:string,alt:string,isPrimary:bool}>
     */
    private function formatGallery(array $product, string $alt): array
    {
        $items = [];
        $externalImageUrl = $this->resolveExternalImageUrl($product);
        if (null !== $externalImageUrl) {
            $items[] = [
                'position' => 0,
                'url' => $externalImageUrl,
                'alt' => $alt,
                'isPrimary' => true,
            ];
        }

        $imageColumns = null === $externalImageUrl
            ? [0 => 'imageName', 1 => 'galleryImage2Name', 2 => 'galleryImage3Name', 3 => 'galleryImage4Name']
            : [1 => 'galleryImage2Name', 2 => 'galleryImage3Name', 3 => 'galleryImage4Name'];

        foreach ($imageColumns as $position => $key) {
            $fileName = null !== $product[$key] ? (string) $product[$key] : null;
            $url = $this->resolveImageUrlFromName($fileName);
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

    private function resolveImageUrlFromName(?string $fileName): ?string
    {
        if (null === $fileName || '' === $fileName) {
            return null;
        }

        return sprintf('/uploads/products/%s', ltrim($fileName, '/'));
    }

    private function normalizeSellingType(mixed $sellingType): string
    {
        if ($sellingType instanceof ProductSellingType) {
            return $sellingType->value;
        }

        if (\is_string($sellingType)) {
            return $sellingType;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $product
     */
    private function resolveExternalImageUrl(array $product): ?string
    {
        $url = $product['imageExternalUrl'] ?? null;

        if (null === $url || '' === trim((string) $url)) {
            return null;
        }

        return (string) $url;
    }
}
