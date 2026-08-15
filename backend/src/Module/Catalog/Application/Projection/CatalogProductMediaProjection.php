<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\Product;

final class CatalogProductMediaProjection
{
    /**
     * @return list<array{position:int,url:string,alt:string,isPrimary:bool}>
     */
    public function formatEntityGallery(Product $product): array
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

    /**
     * @param array<string, mixed> $product
     *
     * @return list<array{position:int,url:string,alt:string,isPrimary:bool}>
     */
    public function formatProjectionGallery(array $product, string $alt): array
    {
        $items = [];
        $externalImageUrl = $this->resolveExternalImageUrl($product);

        if (null !== $externalImageUrl) {
            $items[] = ['position' => 0, 'url' => $externalImageUrl, 'alt' => $alt, 'isPrimary' => true];
        }

        $imageColumns = null === $externalImageUrl
            ? [0 => 'imageName', 1 => 'galleryImage2Name', 2 => 'galleryImage3Name', 3 => 'galleryImage4Name']
            : [1 => 'galleryImage2Name', 2 => 'galleryImage3Name', 3 => 'galleryImage4Name'];

        foreach ($imageColumns as $position => $key) {
            $url = $this->resolveImageUrlFromName(null !== ($product[$key] ?? null) ? (string) $product[$key] : null);
            if (null === $url) {
                continue;
            }

            $items[] = ['position' => $position, 'url' => $url, 'alt' => $alt, 'isPrimary' => 0 === $position];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $product
     */
    public function resolveExternalImageUrl(array $product): ?string
    {
        $url = $product['imageExternalUrl'] ?? null;

        if (null === $url || '' === trim((string) $url)) {
            return null;
        }

        return (string) $url;
    }

    public function resolveImageUrlFromName(?string $fileName): ?string
    {
        if (null === $fileName || '' === $fileName) {
            return null;
        }

        return sprintf('/uploads/products/%s', ltrim($fileName, '/'));
    }
}
