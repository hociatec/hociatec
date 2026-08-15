<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\DTO\ProductVariantCopyData;
use App\Module\Catalog\Application\Factory\ProductVariantFactory;
use App\Module\Catalog\Application\Policy\ProductVariantIdentityPolicy;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantService
{
    public function __construct(
        private ProductVariantFactory $factory,
        private ProductVariantIdentityPolicy $identityPolicy,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $variantDefinitions
     */
    public function resolveVariantGroup(?string $variantGroup, string $name, array $variantDefinitions): string
    {
        $normalized = null !== $variantGroup ? trim($variantGroup) : '';

        if ('' !== $normalized) {
            return $normalized;
        }

        return $this->buildVariantGroupLabel($name);
    }

    public function createVariantCopy(ProductVariantCopyData $data): Product
    {
        return $this->factory->createVariantCopy($data);
    }

    /**
     * @param list<array<string, mixed>>|null $variantDefinitions
     */
    public function assertDefinitionsAreUnique(
        ?string $variantGroup,
        ?Product $currentProduct,
        mixed $currentColorOrAttributes,
        mixed $currentStorageCapacityOrDefinitions = null,
        ?array $variantDefinitions = null,
    ): void {
        if (is_array($currentColorOrAttributes)) {
            /** @var list<array{code:string,label:string,value:string}> $currentAttributes */
            $currentAttributes = array_values($currentColorOrAttributes);
            $variantDefinitions = is_array($currentStorageCapacityOrDefinitions) ? array_values($currentStorageCapacityOrDefinitions) : ($variantDefinitions ?? []);
        } else {
            $currentAttributes = [];

            if (is_string($currentColorOrAttributes) && '' !== trim($currentColorOrAttributes)) {
                $currentAttributes[] = LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, $currentColorOrAttributes);
            }

            if (is_string($currentStorageCapacityOrDefinitions) && '' !== trim($currentStorageCapacityOrDefinitions)) {
                $currentAttributes[] = LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, $currentStorageCapacityOrDefinitions);
            }

            $currentAttributes = array_values(array_filter($currentAttributes));
        }

        $this->identityPolicy->assertDefinitionsAreUnique($variantGroup, $currentProduct, $currentAttributes, $variantDefinitions ?? []);
    }

    private function buildVariantGroupLabel(string $name): string
    {
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($name)) ?? trim($name);
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', $label) ?? $label;
        $label = trim($label);

        return '' !== $label ? $label : $name;
    }
}
