<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Policy;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantIdentityPolicy
{
    public function __construct(private ProductCatalogRepository $productRepository)
    {
    }

    /**
     * @param list<array{code:string,label:string,value:string}> $currentAttributes
     * @param list<array<string, mixed>>                         $variantDefinitions
     */
    public function assertDefinitionsAreUnique(
        ?string $variantGroup,
        ?Product $currentProduct,
        array $currentAttributes,
        array $variantDefinitions,
    ): void {
        if (null === $variantGroup || '' === trim($variantGroup)) {
            return;
        }

        $existingKeys = $this->existingKeys($variantGroup, $currentProduct);
        $currentKey = $this->buildVariantIdentityKey($currentAttributes);

        if (isset($existingKeys[$currentKey])) {
            throw new \InvalidArgumentException(sprintf('La variante %s existe déjà.', $this->formatVariantConflictLabel($currentAttributes)));
        }

        $incomingKeys = [$currentKey => true];
        foreach ($variantDefinitions as $variantDefinition) {
            $variant = $this->normalizeVariantDefinition($variantDefinition);
            if (null === $variant) {
                continue;
            }

            $variantKey = $this->buildVariantIdentityKey($variant);

            if (isset($existingKeys[$variantKey]) || isset($incomingKeys[$variantKey])) {
                throw new \InvalidArgumentException(sprintf('La variante %s existe déjà.', $this->formatVariantConflictLabel($variant)));
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

            $existingKeys[$this->buildVariantIdentityKey($variant->getAttributes())] = true;
        }

        return $existingKeys;
    }

    /**
     * @return list<array{code:string,label:string,value:string}>|null
     */
    private function normalizeVariantDefinition(mixed $variantDefinition): ?array
    {
        if (!is_array($variantDefinition)) {
            return null;
        }

        $attributes = isset($variantDefinition['attributes']) && is_array($variantDefinition['attributes'])
            ? $this->normalizeAttributes(array_values($variantDefinition['attributes']))
            : [];

        if ([] === $attributes) {
            $legacyAttributes = [];
            $variantColor = isset($variantDefinition['color']) && is_string($variantDefinition['color'])
                ? trim($variantDefinition['color'])
                : null;
            $variantStorage = isset($variantDefinition['storageCapacity']) && is_string($variantDefinition['storageCapacity'])
                ? trim($variantDefinition['storageCapacity'])
                : null;

            $variantColorAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, $variantColor);
            if (null !== $variantColorAttribute) {
                $legacyAttributes[] = $variantColorAttribute;
            }
            $variantStorageAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, $variantStorage);
            if (null !== $variantStorageAttribute) {
                $legacyAttributes[] = $variantStorageAttribute;
            }

            $attributes = $legacyAttributes;
        }

        return [] !== $attributes ? $attributes : null;
    }

    /**
     * @param list<array{code:string,label:string,value:string}> $attributes
     */
    private function buildVariantIdentityKey(array $attributes): string
    {
        $pairs = [];

        foreach ($this->normalizeAttributes($attributes) as $attribute) {
            $pairs[] = sprintf('%s=%s', $attribute['code'], mb_strtolower(trim($attribute['value'])));
        }

        sort($pairs, SORT_STRING);

        return implode('|', $pairs);
    }

    /**
     * @param list<array{code:string,label:string,value:string}> $attributes
     */
    private function formatVariantConflictLabel(array $attributes): string
    {
        $parts = array_map(
            static fn (array $attribute): string => $attribute['value'],
            $this->normalizeAttributes($attributes),
        );

        return [] !== $parts ? implode(' / ', $parts) : 'cette variante';
    }

    /**
     * @param list<array<string, mixed>> $attributes
     *
     * @return list<array{code:string,label:string,value:string}>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $attribute) {
            $code = isset($attribute['code']) && is_string($attribute['code']) ? trim(mb_strtolower($attribute['code'])) : '';
            $label = isset($attribute['label']) && is_string($attribute['label']) ? trim($attribute['label']) : '';
            $value = isset($attribute['value']) && is_string($attribute['value']) ? trim($attribute['value']) : '';

            if ('' === $code || '' === $label || '' === $value) {
                continue;
            }

            $normalized[$code] = [
                'code' => $code,
                'label' => $label,
                'value' => $value,
            ];
        }

        return array_values($normalized);
    }
}
