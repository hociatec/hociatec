<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductVariantBatchCreator
{
    public function __construct(
        private ProductVariantService $variants,
        private ProductRepository $products,
        private EntityManagerInterface $entityManager,
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
            $values = $this->normalizeDefinition($definition, $defaultStock);
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
            $values = $this->normalizeDefinition($definition, $defaultStock);
            if (null === $values) {
                continue;
            }

            $this->persistCopy($product, $name, $sku, $slug, $variantGroup, $values, $position);
            ++$position;
        }
    }

    /**
     * @return array{color: ?string, storage: ?string, stock: int}|null
     */
    private function normalizeDefinition(mixed $definition, int $defaultStock): ?array
    {
        if (!is_array($definition)) {
            return null;
        }

        $stock = isset($definition['stock']) ? (int) $definition['stock'] : $defaultStock;
        $color = isset($definition['color']) && is_string($definition['color'])
            ? trim($definition['color'])
            : null;
        $storage = isset($definition['storageCapacity']) && is_string($definition['storageCapacity'])
            ? trim($definition['storageCapacity'])
            : null;

        if ($stock < 0 || ((null === $color || '' === $color) && (null === $storage || '' === $storage))) {
            return null;
        }

        return [
            'color' => '' !== $color ? $color : null,
            'storage' => '' !== $storage ? $storage : null,
            'stock' => $stock,
        ];
    }

    /**
     * @param array{color: ?string, storage: ?string, stock: int} $values
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
        $copy = $this->variants->createVariantCopy(
            $product,
            $name,
            $sku,
            $slug,
            $variantGroup,
            $values['color'],
            $values['storage'],
            $values['stock'],
            $position,
        );
        $this->entityManager->persist($copy);
    }
}
