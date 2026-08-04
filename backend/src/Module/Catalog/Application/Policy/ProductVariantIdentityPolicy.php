<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Policy;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantIdentityPolicy
{
    public function __construct(private ProductCatalogRepository $productRepository)
    {
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

        $existingKeys = $this->existingKeys($variantGroup, $currentProduct);
        $currentKey = $this->buildVariantIdentityKey($currentColor, $currentStorageCapacity);

        if (isset($existingKeys[$currentKey])) {
            throw new \InvalidArgumentException(sprintf('La variante %s existe déjà.', $this->formatVariantConflictLabel($currentColor, $currentStorageCapacity)));
        }

        $incomingKeys = [$currentKey => true];
        foreach ($variantDefinitions as $variantDefinition) {
            $variant = $this->normalizeVariantDefinition($variantDefinition);
            if (null === $variant) {
                continue;
            }

            [$variantColor, $variantStorage] = $variant;
            $variantKey = $this->buildVariantIdentityKey($variantColor, $variantStorage);

            if (isset($existingKeys[$variantKey]) || isset($incomingKeys[$variantKey])) {
                throw new \InvalidArgumentException(sprintf('La variante %s existe déjà.', $this->formatVariantConflictLabel($variantColor, $variantStorage)));
            }

            $incomingKeys[$variantKey] = true;
        }
    }

    /** @return array<string, true> */
    private function existingKeys(string $variantGroup, ?Product $currentProduct): array
    {
        $existingKeys = [];
        foreach ($this->productRepository->findByVariantGroupOrdered($variantGroup) as $variant) {
            if (null !== $currentProduct && $variant->getId() === $currentProduct->getId()) {
                continue;
            }

            $existingKeys[$this->buildVariantIdentityKey($variant->getColor(), $variant->getStorageCapacity())] = true;
        }

        return $existingKeys;
    }

    /** @return array{0:string|null,1:string|null}|null */
    private function normalizeVariantDefinition(mixed $variantDefinition): ?array
    {
        if (!is_array($variantDefinition)) {
            return null;
        }

        $variantColor = isset($variantDefinition['color']) && is_string($variantDefinition['color'])
            ? trim($variantDefinition['color'])
            : null;
        $variantStorage = isset($variantDefinition['storageCapacity']) && is_string($variantDefinition['storageCapacity'])
            ? trim($variantDefinition['storageCapacity'])
            : null;

        if ((null === $variantColor || '' === $variantColor) && (null === $variantStorage || '' === $variantStorage)) {
            return null;
        }

        return [$variantColor, $variantStorage];
    }

    private function buildVariantIdentityKey(?string $color, ?string $storageCapacity): string
    {
        return sprintf(
            '%s|%s',
            null !== $color ? mb_strtolower(trim($color)) : '',
            null !== $storageCapacity ? mb_strtolower(trim($storageCapacity)) : '',
        );
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
