<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Factory;

use App\Module\Catalog\Application\DTO\ProductVariantCopyData;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Application\Workflow\ProductVariantService;
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
            $values = $this->normalizeDefinition($definition, $defaultStock, $product->getPriceCents());
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
            $values = $this->normalizeDefinition($definition, $defaultStock, $product->getPriceCents());
            if (null === $values) {
                continue;
            }

            $this->persistCopy($product, $name, $sku, $slug, $variantGroup, $values, $position);
            ++$position;
        }
    }

    /**
     * @return array{color: ?string, storage: ?string, stock: int, priceCents: int}|null
     */
    private function normalizeDefinition(mixed $definition, int $defaultStock, int $defaultPriceCents): ?array
    {
        if (!is_array($definition)) {
            return null;
        }

        $stock = isset($definition['stock']) ? (int) $definition['stock'] : $defaultStock;
        $priceCents = array_key_exists('priceCents', $definition) && null !== $definition['priceCents']
            ? (int) $definition['priceCents']
            : $defaultPriceCents;
        $color = isset($definition['color']) && is_string($definition['color'])
            ? trim($definition['color'])
            : null;
        $storage = isset($definition['storageCapacity']) && is_string($definition['storageCapacity'])
            ? trim($definition['storageCapacity'])
            : null;

        if (
            $stock < 0
            || $priceCents < 0
            || ((null === $color || '' === $color) && (null === $storage || '' === $storage))
        ) {
            return null;
        }

        return [
            'color' => '' !== $color ? $color : null,
            'storage' => '' !== $storage ? $storage : null,
            'stock' => $stock,
            'priceCents' => $priceCents,
        ];
    }

    /**
     * @param array{color: ?string, storage: ?string, stock: int, priceCents: int} $values
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
        $copy = $this->variants->createVariantCopy(new ProductVariantCopyData(
            template: $product,
            baseName: $name,
            baseSku: $sku,
            baseSlug: $slug,
            variantGroup: $variantGroup,
            color: $values['color'],
            storageCapacity: $values['storage'],
            stock: $values['stock'],
            priceCents: $values['priceCents'],
            position: $position,
        ));
        $this->persistence->persist($copy);
    }
}
