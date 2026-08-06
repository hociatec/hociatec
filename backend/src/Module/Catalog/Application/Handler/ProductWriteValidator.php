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
        $this->rules->assertUniqueness($normalizedSku, null);
        $this->variants->assertDefinitionsAreUnique($variantGroup, null, $command->variant->color, $command->variant->storageCapacity, $command->variant->definitions);
    }

    public function validateUpdate(ProductWriteCommand $command, Product $product, string $normalizedSku, string $variantGroup): void
    {
        $this->rules->assertValidData($command->core, $normalizedSku);
        $this->rules->assertUniqueness($normalizedSku, $product->getId());
        $this->variants->assertDefinitionsAreUnique($variantGroup, $product, $command->variant->color, $command->variant->storageCapacity, $command->variant->definitions);
    }
}
