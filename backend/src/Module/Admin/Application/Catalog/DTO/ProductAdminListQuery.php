<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use App\Module\Catalog\Application\Query\ProductAdminCriteria;

final readonly class ProductAdminListQuery
{
    public int $page;
    public int $perPage;
    public ?string $categorySlug;
    public ?string $search;
    public ?bool $featured;
    public ?string $sellingType;
    public ?int $minPriceCents;
    public ?int $maxPriceCents;
    public bool $lowStock;
    public ?string $sort;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->page = (int) $data['page'];
        $this->perPage = (int) $data['perPage'];
        $this->categorySlug = $data['categorySlug'];
        $this->search = $data['search'];
        $this->featured = $data['featured'];
        $this->sellingType = $data['sellingType'];
        $this->minPriceCents = $data['minPriceCents'];
        $this->maxPriceCents = $data['maxPriceCents'];
        $this->lowStock = (bool) $data['lowStock'];
        $this->sort = $data['sort'];
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function criteria(): ProductAdminCriteria
    {
        return new ProductAdminCriteria(
            categorySlug: $this->categorySlug,
            search: $this->search,
            onlyFeatured: $this->featured,
            sellingType: $this->sellingType,
            minPriceCents: $this->minPriceCents,
            maxPriceCents: $this->maxPriceCents,
            lowStockOnly: $this->lowStock,
            sort: $this->sort,
            limit: $this->perPage,
            offset: $this->offset(),
        );
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['page', 'perPage', 'categorySlug', 'search', 'featured', 'sellingType', 'minPriceCents', 'maxPriceCents', 'lowStock', 'sort'];
        $defaults = array_fill_keys($keys, null);
        $defaults['page'] = 1;
        $defaults['perPage'] = 25;
        $defaults['lowStock'] = false;
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
