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
    public int $priceCents;
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
        $this->priceCents = null !== $data['priceCents'] ? (int) $data['priceCents'] : $this->template->getPriceCents();
        $this->position = (int) $data['position'];
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['template', 'baseName', 'baseSku', 'baseSlug', 'variantGroup', 'color', 'storageCapacity', 'stock', 'priceCents', 'position'];
        $defaults = array_fill_keys($keys, null);
        $defaults['stock'] = 0;
        $defaults['priceCents'] = null;
        $defaults['position'] = 1;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        if (
            array_key_exists(8, $values)
            && !array_key_exists(9, $values)
            && !array_key_exists('priceCents', $values)
            && !array_key_exists('position', $values)
        ) {
            $defaults['position'] = (int) $values[8];
            $defaults['priceCents'] = null;
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
