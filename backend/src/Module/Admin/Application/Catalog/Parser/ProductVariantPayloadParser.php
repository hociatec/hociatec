<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Parser;

use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;
use App\Shared\Application\Exception\PublicInvalidArgumentException;

final class ProductVariantPayloadParser
{
    public function __construct(private readonly ?ProductAttributePayloadParser $attributes = null)
    {
    }

    /**
     * @return list<array{attributes:list<array{code:string,label:string,value:string}>, stock: int, salePriceCents?: int|null, rentalPriceCents?: int|null}>
     */
    public function parse(mixed $value): array
    {
        if (!is_string($value) || '' === trim($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw new PublicInvalidArgumentException('Définition des variantes invalide.');
        }

        $variants = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $stock = isset($row['stock']) ? (int) $row['stock'] : 0;
            if ($stock < 0) {
                throw new PublicInvalidArgumentException('Le stock des variantes doit être positif.');
            }

            $salePriceCents = array_key_exists('salePrice', $row)
                ? ProductFormValueNormalizer::priceToCents($row['salePrice'])
                : null;
            $rentalPriceCents = array_key_exists('rentalPrice', $row)
                ? ProductFormValueNormalizer::priceToCents($row['rentalPrice'])
                : null;
            if (null !== $salePriceCents && $salePriceCents < 0) {
                throw new PublicInvalidArgumentException('Le prix de vente des variantes est invalide.');
            }
            if (null !== $rentalPriceCents && $rentalPriceCents < 0) {
                throw new PublicInvalidArgumentException('Le prix de location des variantes est invalide.');
            }

            $attributes = $this->resolveAttributes($row);

            if ([] === $attributes) {
                continue;
            }

            $legacyOnly = $this->buildLegacyVariant($attributes);
            if (null !== $legacyOnly) {
                $variant = $legacyOnly + ['stock' => $stock];
            } else {
                $variant = [
                    'attributes' => $attributes,
                    'stock' => $stock,
                ];
            }

            if (null !== $salePriceCents) {
                $variant['salePriceCents'] = $salePriceCents;
            }
            if (null !== $rentalPriceCents) {
                $variant['rentalPriceCents'] = $rentalPriceCents;
            }

            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<array{code:string,label:string,value:string}>
     */
    private function resolveAttributes(array $row): array
    {
        $attributes = isset($row['attributes']) ? ($this->attributes ?? new ProductAttributePayloadParser())->parse(json_encode($row['attributes'])) : [];

        $legacyAttributes = [];

        $color = ProductFormValueNormalizer::optionalString($row['color'] ?? null);
        $colorAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, $color);
        if (null !== $colorAttribute) {
            $legacyAttributes[] = $colorAttribute;
        }

        $storage = ProductFormValueNormalizer::optionalString($row['storageCapacity'] ?? null);
        $storageAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, $storage);
        if (null !== $storageAttribute) {
            $legacyAttributes[] = $storageAttribute;
        }

        foreach ($legacyAttributes as $attribute) {
            $attributes[$attribute['code']] = $attribute;
        }

        return array_values($attributes);
    }

    /**
     * @param list<array{code:string,label:string,value:string}> $attributes
     *
     * @return array{color?:string,storageCapacity?:string,memoryRam?:string}|null
     */
    private function buildLegacyVariant(array $attributes): ?array
    {
        $legacy = [];

        foreach ($attributes as $attribute) {
            $code = trim((string) ($attribute['code'] ?? ''));
            $value = trim((string) ($attribute['value'] ?? ''));

            if ('' === $value) {
                continue;
            }

            if (LegacyProductAttribute::COLOR_CODE === $code) {
                $legacy['color'] = $value;
                continue;
            }

            if (LegacyProductAttribute::STORAGE_CODE === $code) {
                $legacy['storageCapacity'] = $value;
                continue;
            }

            if (LegacyProductAttribute::MEMORY_RAM_CODE === $code) {
                $legacy['memoryRam'] = $value;
                continue;
            }

            return null;
        }

        return [] !== $legacy ? $legacy : null;
    }
}
