<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Service;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;

final readonly class ProductVariantService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductCatalogRules $rules,
    ) {
    }

    /**
     * @param array<int, mixed> $variantDefinitions
     */
    public function resolveVariantGroup(?string $variantGroup, string $name, array $variantDefinitions): string
    {
        $normalized = null !== $variantGroup ? trim($variantGroup) : '';

        if ('' !== $normalized) {
            return $normalized;
        }

        return $this->buildVariantGroupLabel($name);
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
        $variantName = $this->buildVariantName($baseName, $color, $storageCapacity);
        $variantSku = $this->buildVariantSku($baseSku, $color, $storageCapacity, $index);
        $variantSlug = $this->buildVariantSlug($baseSlug ?? $baseName, $color, $storageCapacity, $index);

        $variantProduct = new Product(
            $variantName,
            $variantSlug,
            $variantSku,
            $template->getDescription(),
            $template->getPriceCents(),
            $stock,
            $template->getCategory(),
        );

        $variantProduct
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

        return $variantProduct;
    }

    /**
     * @param array<int, mixed> $variantDefinitions
     */
    public function assertDefinitionsAreUnique(
        ?string $variantGroup,
        ?Product $currentProduct,
        ?string $currentColor,
        ?string $currentStorageCapacity,
        array $variantDefinitions,
    ): void {
        if (null === $variantGroup || '' === trim($variantGroup)) {
            return;
        }

        $existingKeys = [];

        foreach ($this->productRepository->findByVariantGroupOrdered($variantGroup) as $variant) {
            if (null !== $currentProduct && $variant->getId() === $currentProduct->getId()) {
                continue;
            }

            $existingKeys[$this->buildVariantIdentityKey($variant->getColor(), $variant->getStorageCapacity())] = true;
        }

        $currentKey = $this->buildVariantIdentityKey($currentColor, $currentStorageCapacity);

        if (isset($existingKeys[$currentKey])) {
            throw new \InvalidArgumentException(sprintf('La variante %s existe déjà.', $this->formatVariantConflictLabel($currentColor, $currentStorageCapacity)));
        }

        $incomingKeys = [$currentKey => true];

        foreach ($variantDefinitions as $variantDefinition) {
            if (!is_array($variantDefinition)) {
                continue;
            }

            $variantColor = isset($variantDefinition['color']) && is_string($variantDefinition['color'])
                ? trim($variantDefinition['color'])
                : null;
            $variantStorage = isset($variantDefinition['storageCapacity']) && is_string($variantDefinition['storageCapacity'])
                ? trim($variantDefinition['storageCapacity'])
                : null;

            if ((null === $variantColor || '' === $variantColor) && (null === $variantStorage || '' === $variantStorage)) {
                continue;
            }

            $variantKey = $this->buildVariantIdentityKey($variantColor, $variantStorage);

            if (isset($existingKeys[$variantKey]) || isset($incomingKeys[$variantKey])) {
                throw new \InvalidArgumentException(sprintf('La variante %s existe déjà.', $this->formatVariantConflictLabel($variantColor, $variantStorage)));
            }

            $incomingKeys[$variantKey] = true;
        }
    }

    private function buildVariantGroupLabel(string $name): string
    {
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($name)) ?? trim($name);
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', $label) ?? $label;
        $label = trim($label);

        return '' !== $label ? $label : $name;
    }

    private function buildVariantName(string $baseName, ?string $color, ?string $storageCapacity): string
    {
        $parts = [];

        if (null !== $color && '' !== $color) {
            $parts[] = $color;
        }

        if (null !== $storageCapacity && '' !== $storageCapacity) {
            $parts[] = $storageCapacity;
        }

        if ([] === $parts) {
            return $baseName;
        }

        return sprintf('%s (%s)', $baseName, implode(') (', $parts));
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

    private function buildVariantIdentityKey(?string $color, ?string $storageCapacity): string
    {
        $normalizedColor = null !== $color ? mb_strtolower(trim($color)) : '';
        $normalizedStorage = null !== $storageCapacity ? mb_strtolower(trim($storageCapacity)) : '';

        return sprintf('%s|%s', $normalizedColor, $normalizedStorage);
    }

    private function formatVariantConflictLabel(?string $color, ?string $storageCapacity): string
    {
        $parts = array_values(array_filter([
            null !== $color ? trim($color) : '',
            null !== $storageCapacity ? trim($storageCapacity) : '',
        ]));

        return [] !== $parts ? implode(' / ', $parts) : 'cette variante';
    }
}
