<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Handler;

use App\Module\Catalog\Application\Calculator\ProductCatalogRules;
use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Application\Workflow\ProductVariantService;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductWriteValidator
{
    public function __construct(
        private ProductCatalogRules $rules,
        private ProductVariantService $variants,
    ) {
    }

    public function resolveSlug(ProductWriteCommand $command, ?int $productId): string
    {
        return $this->rules->resolveSlug($command->core->slug, $command->core->name, $productId);
    }

    public function normalizedSku(ProductWriteCommand $command): string
    {
        return strtoupper($command->core->sku);
    }

    public function validateCreate(ProductWriteCommand $command, string $normalizedSku, string $variantGroup): void
    {
        $this->rules->assertValidData($command->core, $normalizedSku);
        $this->assertCategoryAttributeConfiguration($command);
        $this->rules->assertUniqueness($normalizedSku, null);
        $this->variants->assertDefinitionsAreUnique($variantGroup, null, $command->variant->attributes, $command->variant->definitions);
    }

    public function validateUpdate(ProductWriteCommand $command, Product $product, string $normalizedSku, string $variantGroup): void
    {
        $this->rules->assertValidData($command->core, $normalizedSku);
        $this->assertCategoryAttributeConfiguration($command);
        $this->rules->assertUniqueness($normalizedSku, $product->getId());
        $this->variants->assertDefinitionsAreUnique($variantGroup, $product, $command->variant->attributes, $command->variant->definitions);
    }

    private function assertCategoryAttributeConfiguration(ProductWriteCommand $command): void
    {
        $definitions = $command->core->category->getAttributeDefinitions();

        $this->assertAttributesMatchDefinitions($command->variant->attributes, $definitions);

        foreach ($command->variant->definitions as $variantDefinition) {
            if (!is_array($variantDefinition)) {
                continue;
            }

            $attributes = is_array($variantDefinition['attributes'] ?? null) ? $variantDefinition['attributes'] : [];
            $this->assertAttributesMatchDefinitions($attributes, $definitions);
        }
    }

    /**
     * @param list<array<string, mixed>> $attributes
     * @param list<array<string, mixed>> $definitions
     */
    private function assertAttributesMatchDefinitions(array $attributes, array $definitions): void
    {
        $definitionMap = [];

        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $code = isset($definition['code']) && is_string($definition['code']) ? trim(mb_strtolower($definition['code'])) : '';
            if ('' === $code) {
                continue;
            }

            $definitionMap[$code] = $definition;
        }

        $seenValues = [];
        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $code = isset($attribute['code']) && is_string($attribute['code']) ? trim(mb_strtolower($attribute['code'])) : '';
            $value = isset($attribute['value']) && is_string($attribute['value']) ? trim($attribute['value']) : '';

            if ('' === $code) {
                continue;
            }

            $seenValues[$code] = $value;

            if (!isset($definitionMap[$code])) {
                continue;
            }

            $definition = $definitionMap[$code];
            $label = isset($definition['label']) && is_string($definition['label']) ? trim($definition['label']) : $code;
            $inputType = isset($definition['inputType']) && is_string($definition['inputType']) ? trim(mb_strtolower($definition['inputType'])) : 'text';
            $options = is_array($definition['options'] ?? null) ? $definition['options'] : [];

            if ('' === $value) {
                continue;
            }

            match ($inputType) {
                'number' => $this->assertNumberAttribute($value, $label),
                'boolean' => $this->assertBooleanAttribute($value, $label),
                'color' => $this->assertColorAttribute($value, $label),
                'select' => $this->assertSelectAttribute($value, $options, $label),
                default => null,
            };
        }

        foreach ($definitionMap as $code => $definition) {
            if (!(bool) ($definition['isRequired'] ?? false)) {
                continue;
            }

            if ('' !== trim((string) ($seenValues[$code] ?? ''))) {
                continue;
            }

            $label = isset($definition['label']) && is_string($definition['label']) ? trim($definition['label']) : $code;
            throw new \InvalidArgumentException(sprintf('%s est obligatoire pour cette catégorie.', $label));
        }
    }

    private function assertNumberAttribute(string $value, string $label): void
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf('%s doit être un nombre valide.', $label));
        }
    }

    private function assertBooleanAttribute(string $value, string $label): void
    {
        if (!in_array(trim(mb_strtolower($value)), ['oui', 'non', 'true', 'false', '1', '0'], true)) {
            throw new \InvalidArgumentException(sprintf('%s doit valoir Oui ou Non.', $label));
        }
    }

    private function assertColorAttribute(string $value, string $label): void
    {
        if (1 !== preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/iu', $value)) {
            throw new \InvalidArgumentException(sprintf('%s doit être une couleur hexadécimale valide.', $label));
        }
    }

    /**
     * @param list<mixed> $options
     */
    private function assertSelectAttribute(string $value, array $options, string $label): void
    {
        $allowedValues = [];

        foreach ($options as $option) {
            if (!is_scalar($option) && null !== $option) {
                continue;
            }

            $normalized = trim(mb_strtolower((string) $option));
            if ('' === $normalized) {
                continue;
            }

            $allowedValues[$normalized] = true;
        }

        if ([] === $allowedValues) {
            return;
        }

        if (!isset($allowedValues[trim(mb_strtolower($value))])) {
            throw new \InvalidArgumentException(sprintf('%s doit correspondre à une option autorisée.', $label));
        }
    }
}
