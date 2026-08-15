<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Projection;

use App\Module\Catalog\Domain\Entity\ProductSellingType;

final class ProductCatalogProjectionSupport
{
    /**
     * @param array<string, mixed> $product
     */
    public function resolveSellingType(array $product, ?string $preferredSellingType): string
    {
        if (null !== $preferredSellingType && $this->supportsSellingType($product, $preferredSellingType)) {
            return $preferredSellingType;
        }

        foreach ([ProductSellingType::Sale->value, ProductSellingType::Rental->value] as $sellingType) {
            if ($this->supportsSellingType($product, $sellingType)) {
                return $sellingType;
            }
        }

        throw new \LogicException('Le produit projeté ne supporte aucun mode de commercialisation.');
    }

    /**
     * @param array<string, mixed> $product
     */
    public function resolvePriceCents(array $product, string $sellingType): int
    {
        $field = ProductSellingType::Rental->value === $sellingType ? 'rentalPriceCents' : 'salePriceCents';
        $value = $product[$field] ?? null;

        if (null === $value && isset($product['priceCents'])) {
            return (int) $product['priceCents'];
        }

        if (null === $value) {
            throw new \LogicException(sprintf('Le prix "%s" est manquant pour la projection du produit.', $field));
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $product
     */
    public function supportsSellingType(array $product, string $sellingType): bool
    {
        $availabilityField = ProductSellingType::Sale->value === $sellingType ? 'availableForSale' : 'availableForRental';
        if (array_key_exists($availabilityField, $product)) {
            return (bool) $product[$availabilityField];
        }

        $explicitSellingType = ProductSellingType::tryFrom((string) ($product['sellingType'] ?? ''));
        if ($explicitSellingType instanceof ProductSellingType) {
            return $explicitSellingType->value === $sellingType;
        }

        $priceField = ProductSellingType::Rental->value === $sellingType ? 'rentalPriceCents' : 'salePriceCents';
        if (null !== ($product[$priceField] ?? null)) {
            return true;
        }

        return isset($product['priceCents']) && ProductSellingType::Sale->value === $sellingType;
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return list<string>
     */
    public function availableModes(array $product): array
    {
        return array_values(array_filter([
            $this->supportsSellingType($product, ProductSellingType::Sale->value) ? ProductSellingType::Sale->value : null,
            $this->supportsSellingType($product, ProductSellingType::Rental->value) ? ProductSellingType::Rental->value : null,
        ]));
    }

    /**
     * @return list<array{code:string,label:string,value:string}>
     */
    public function normalizeAttributes(mixed $attributes): array
    {
        if (!is_array($attributes)) {
            return [];
        }

        $normalized = [];

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $code = isset($attribute['code']) ? trim((string) $attribute['code']) : '';
            $label = isset($attribute['label']) ? trim((string) $attribute['label']) : '';
            $value = isset($attribute['value']) ? trim((string) $attribute['value']) : '';

            if ('' !== $code && '' !== $label && '' !== $value) {
                $normalized[] = ['code' => $code, 'label' => $label, 'value' => $value];
            }
        }

        return $normalized;
    }

    public function attributeValue(mixed $attributes, string $code): ?string
    {
        foreach ($this->normalizeAttributes($attributes) as $attribute) {
            if ($attribute['code'] === $code) {
                return $attribute['value'];
            }
        }

        return null;
    }
}
