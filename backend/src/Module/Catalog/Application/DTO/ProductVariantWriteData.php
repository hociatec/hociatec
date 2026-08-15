<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;

final class ProductVariantWriteData
{
    public ?string $group;
    public ?int $releaseYear;

    /** @var list<array{code:string,label:string,value:string}> */
    public array $attributes;

    /** @var list<array<string, mixed>> */
    public array $definitions;

    public ?string $storageCapacity;
    public ?string $memoryRam;
    public ?string $color;

    /**
     * @param list<array{code:string,label:string,value:string}> $attributes
     * @param list<array<string, mixed>>                          $definitions
     */
    public function __construct(
        ?string $group,
        ?int $releaseYear,
        array $attributes = [],
        array $definitions = [],
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
    ) {
        $this->group = $group;
        $this->releaseYear = $releaseYear;
        $this->attributes = [] !== $attributes ? array_values($attributes) : $this->buildLegacyAttributes($storageCapacity, $memoryRam, $color);
        $this->definitions = $definitions;
        $this->storageCapacity = $storageCapacity ?? $this->attributeValue(LegacyProductAttribute::STORAGE_CODE);
        $this->memoryRam = $memoryRam ?? $this->attributeValue(LegacyProductAttribute::MEMORY_RAM_CODE);
        $this->color = $color ?? $this->attributeValue(LegacyProductAttribute::COLOR_CODE);
    }

    /**
     * @return list<array{code:string,label:string,value:string}>
     */
    private function buildLegacyAttributes(?string $storageCapacity, ?string $memoryRam, ?string $color): array
    {
        $attributes = [];

        $storageAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, $storageCapacity);
        if (null !== $storageAttribute) {
            $attributes[] = $storageAttribute;
        }

        $memoryAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::MEMORY_RAM_CODE, $memoryRam);
        if (null !== $memoryAttribute) {
            $attributes[] = $memoryAttribute;
        }

        $colorAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, $color);
        if (null !== $colorAttribute) {
            $attributes[] = $colorAttribute;
        }

        return $attributes;
    }

    private function attributeValue(string $code): ?string
    {
        foreach ($this->attributes as $attribute) {
            if (($attribute['code'] ?? null) === $code) {
                return $attribute['value'] ?? null;
            }
        }

        return null;
    }
}
