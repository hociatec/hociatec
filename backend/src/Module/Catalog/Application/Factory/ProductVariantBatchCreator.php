<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Factory;

use App\Module\Catalog\Application\DTO\ProductVariantCopyData;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Application\Workflow\ProductVariantService;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;
use App\Module\Catalog\Domain\Entity\Product;
use App\Shared\Application\UnitOfWork;

final readonly class ProductVariantBatchCreator
{
    public function __construct(
        private ProductVariantService $variants,
        private ProductCatalogRepository $products,
        private UnitOfWork $persistence,
    ) {
    }

    /** @param list<array<string, mixed>> $definitions */
    public function forNewProduct(
        Product $product,
        string $name,
        string $sku,
        ?string $slug,
        string $variantGroup,
        int $defaultStock,
        array $definitions,
    ): void {
        foreach ($definitions as $index => $definition) {
            $values = $this->normalizeDefinition(
                $definition,
                $defaultStock,
                $product->getSalePriceCents(),
                $product->getRentalPriceCents(),
                $product->isAvailableForSale(),
                $product->isAvailableForRental(),
            );
            if (null === $values) {
                continue;
            }

            $this->persistCopy(
                $product,
                $name,
                $sku,
                $slug,
                $variantGroup,
                $values,
                $index + 2,
            );
        }
    }

    /** @param list<array<string, mixed>> $definitions */
    public function forExistingProduct(
        Product $product,
        string $name,
        string $sku,
        ?string $slug,
        string $variantGroup,
        int $defaultStock,
        array $definitions,
    ): void {
        if ([] === $definitions || '' === $variantGroup) {
            return;
        }

        $position = count($this->products->findByVariantGroupOrdered($variantGroup)) + 1;
        foreach ($definitions as $definition) {
            $values = $this->normalizeDefinition(
                $definition,
                $defaultStock,
                $product->getSalePriceCents(),
                $product->getRentalPriceCents(),
                $product->isAvailableForSale(),
                $product->isAvailableForRental(),
            );
            if (null === $values) {
                continue;
            }

            $this->persistCopy($product, $name, $sku, $slug, $variantGroup, $values, $position);
            ++$position;
        }
    }

    /**
     * @return array{attributes:list<array{code:string,label:string,value:string}>, stock: int, salePriceCents: ?int, rentalPriceCents: ?int}|null
     */
    private function normalizeDefinition(
        mixed $definition,
        int $defaultStock,
        ?int $defaultSalePriceCents,
        ?int $defaultRentalPriceCents,
        bool $availableForSale,
        bool $availableForRental,
    ): ?array {
        if (!is_array($definition)) {
            return null;
        }

        $stock = isset($definition['stock']) ? (int) $definition['stock'] : $defaultStock;
        $salePriceCents = array_key_exists('salePriceCents', $definition) && null !== $definition['salePriceCents']
            ? (int) $definition['salePriceCents']
            : $defaultSalePriceCents;
        $rentalPriceCents = array_key_exists('rentalPriceCents', $definition) && null !== $definition['rentalPriceCents']
            ? (int) $definition['rentalPriceCents']
            : $defaultRentalPriceCents;
        $attributes = isset($definition['attributes']) && is_array($definition['attributes'])
            ? $this->normalizeAttributes(array_values($definition['attributes']))
            : [];

        if ([] === $attributes) {
            $color = isset($definition['color']) && is_string($definition['color'])
                ? trim($definition['color'])
                : null;
            $storage = isset($definition['storageCapacity']) && is_string($definition['storageCapacity'])
                ? trim($definition['storageCapacity'])
                : null;

            $colorAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, $color);
            if (null !== $colorAttribute) {
                $attributes[] = $colorAttribute;
            }

            $storageAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, $storage);
            if (null !== $storageAttribute) {
                $attributes[] = $storageAttribute;
            }
        }

        if (
            $stock < 0
            || ($availableForSale && (null === $salePriceCents || $salePriceCents < 0))
            || ($availableForRental && (null === $rentalPriceCents || $rentalPriceCents < 0))
            || [] === $attributes
        ) {
            return null;
        }

        return [
            'attributes' => $attributes,
            'stock' => $stock,
            'salePriceCents' => $salePriceCents,
            'rentalPriceCents' => $rentalPriceCents,
        ];
    }

    /**
     * @param array{attributes:list<array{code:string,label:string,value:string}>, stock: int, salePriceCents: ?int, rentalPriceCents: ?int} $values
     */
    private function persistCopy(
        Product $product,
        string $name,
        string $sku,
        ?string $slug,
        string $variantGroup,
        array $values,
        int $position,
    ): void {
        $copy = $this->variants->createVariantCopy(new ProductVariantCopyData([
            'template' => $product,
            'baseName' => $name,
            'baseSku' => $sku,
            'baseSlug' => $slug,
            'variantGroup' => $variantGroup,
            'attributes' => $values['attributes'],
            'stock' => $values['stock'],
            'salePriceCents' => $values['salePriceCents'],
            'rentalPriceCents' => $values['rentalPriceCents'],
            'position' => $position,
        ]));
        $this->persistence->persist($copy);
    }

    /**
     * @param array<array-key, array<string, mixed>> $attributes
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
