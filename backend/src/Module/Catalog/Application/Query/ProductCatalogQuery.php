<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

readonly class ProductCatalogQuery
{
    public int $page;
    public int $perPage;
    public ?string $categorySlug;
    public ?string $search;
    public ?bool $onlyFeatured;
    public ?string $sellingType;
    public ?string $brand;
    public ?string $storageCapacity;
    public ?string $memoryRam;
    public ?string $color;
    public ?int $minPriceCents;
    public ?int $maxPriceCents;
    public ?bool $inStockOnly;
    public ?string $sort;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->page = (int) $data['page'];
        $this->perPage = (int) $data['perPage'];
        $this->categorySlug = $data['categorySlug'];
        $this->search = $data['search'];
        $this->onlyFeatured = $data['onlyFeatured'];
        $this->sellingType = $data['sellingType'];
        $this->brand = $data['brand'];
        $this->storageCapacity = $data['storageCapacity'];
        $this->memoryRam = $data['memoryRam'];
        $this->color = $data['color'];
        $this->minPriceCents = $data['minPriceCents'];
        $this->maxPriceCents = $data['maxPriceCents'];
        $this->inStockOnly = $data['inStockOnly'];
        $this->sort = $data['sort'];
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function criteria(): ProductCatalogCriteria
    {
        return ProductCatalogCriteria::fromCatalogQuery($this);
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['page', 'perPage', 'categorySlug', 'search', 'onlyFeatured', 'sellingType', 'brand', 'storageCapacity', 'memoryRam', 'color', 'minPriceCents', 'maxPriceCents', 'inStockOnly', 'sort'];
        $defaults = array_fill_keys($keys, null);
        $defaults['page'] = 1;
        $defaults['perPage'] = 12;
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
