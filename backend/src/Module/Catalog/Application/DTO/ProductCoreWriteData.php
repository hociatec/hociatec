<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;

final readonly class ProductCoreWriteData
{
    public string $name;
    public string $sku;
    public ?string $slug;
    public string $description;
    public ?string $shortDescription;
    public int $priceCents;
    public int $stock;
    public bool $isPublished;
    public bool $isFeaturedHome;
    public Category $category;
    public ?string $imageAlt;
    public string $sellingType;
    public ?Brand $brand;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->name = (string) $data['name'];
        $this->sku = (string) $data['sku'];
        $this->slug = $data['slug'];
        $this->description = (string) $data['description'];
        $this->shortDescription = $data['shortDescription'];
        $this->priceCents = (int) $data['priceCents'];
        $this->stock = (int) $data['stock'];
        $this->isPublished = (bool) $data['isPublished'];
        $this->isFeaturedHome = (bool) $data['isFeaturedHome'];
        $this->category = $data['category'];
        $this->imageAlt = $data['imageAlt'];
        $this->sellingType = (string) $data['sellingType'];
        $this->brand = $data['brand'];
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['name', 'sku', 'slug', 'description', 'shortDescription', 'priceCents', 'stock', 'isPublished', 'isFeaturedHome', 'category', 'imageAlt', 'sellingType', 'brand'];
        $defaults = array_fill_keys($keys, null);
        $defaults['description'] = '';
        $defaults['priceCents'] = 0;
        $defaults['stock'] = 0;
        $defaults['isPublished'] = false;
        $defaults['isFeaturedHome'] = false;
        $defaults['sellingType'] = 'sale';
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
