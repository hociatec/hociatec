<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantCopyData
{
    public Product $template;
    public string $baseName;
    public string $baseSku;
    public ?string $baseSlug;
    public string $variantGroup;
    public ?string $color;
    public ?string $storageCapacity;
    public int $stock;
    public int $position;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->template = $data['template'];
        $this->baseName = (string) $data['baseName'];
        $this->baseSku = (string) $data['baseSku'];
        $this->baseSlug = $data['baseSlug'];
        $this->variantGroup = (string) $data['variantGroup'];
        $this->color = $data['color'];
        $this->storageCapacity = $data['storageCapacity'];
        $this->stock = (int) $data['stock'];
        $this->position = (int) $data['position'];
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['template', 'baseName', 'baseSku', 'baseSlug', 'variantGroup', 'color', 'storageCapacity', 'stock', 'position'];
        $defaults = array_fill_keys($keys, null);
        $defaults['stock'] = 0;
        $defaults['position'] = 0;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
