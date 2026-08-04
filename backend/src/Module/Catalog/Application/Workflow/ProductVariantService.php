<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Calculator\ProductCatalogRules;
use App\Module\Catalog\Application\Factory\ProductVariantFactory;
use App\Module\Catalog\Application\Policy\ProductVariantIdentityPolicy;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantService
{
    private ProductVariantFactory $factory;

    private ProductVariantIdentityPolicy $identityPolicy;

    public function __construct(
        private ProductCatalogRepository $productRepository,
        private ProductCatalogRules $rules,
        ?ProductVariantFactory $factory = null,
        ?ProductVariantIdentityPolicy $identityPolicy = null,
    ) {
        $this->factory = $factory ?? new ProductVariantFactory($this->productRepository, $this->rules);
        $this->identityPolicy = $identityPolicy ?? new ProductVariantIdentityPolicy($this->productRepository);
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
        return $this->factory->createVariantCopy($template, $baseName, $baseSku, $baseSlug, $variantGroup, $color, $storageCapacity, $stock, $index);
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
        $this->identityPolicy->assertDefinitionsAreUnique($variantGroup, $currentProduct, $currentColor, $currentStorageCapacity, $variantDefinitions);
    }

    private function buildVariantGroupLabel(string $name): string
    {
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($name)) ?? trim($name);
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', $label) ?? $label;
        $label = trim($label);

        return '' !== $label ? $label : $name;
    }
}
