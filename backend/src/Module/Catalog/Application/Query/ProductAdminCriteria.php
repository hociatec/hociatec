<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Query;

final readonly class ProductAdminCriteria
{
    public ?string $categorySlug;
    public ?string $search;
    public ?bool $onlyFeatured;
    public ?string $sellingType;
    public ?int $minPriceCents;
    public ?int $maxPriceCents;
    public ?bool $lowStockOnly;
    public ?string $sort;
    public int $limit;
    public int $offset;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->categorySlug = $data['categorySlug'];
        $this->search = $data['search'];
        $this->onlyFeatured = $data['onlyFeatured'];
        $this->sellingType = $data['sellingType'];
        $this->minPriceCents = $data['minPriceCents'];
        $this->maxPriceCents = $data['maxPriceCents'];
        $this->lowStockOnly = $data['lowStockOnly'];
        $this->sort = $data['sort'];
        $this->limit = (int) $data['limit'];
        $this->offset = (int) $data['offset'];
    }

    public function withoutSortAndPagination(): self
    {
        return new self(
            categorySlug: $this->categorySlug,
            search: $this->search,
            onlyFeatured: $this->onlyFeatured,
            sellingType: $this->sellingType,
            minPriceCents: $this->minPriceCents,
            maxPriceCents: $this->maxPriceCents,
            lowStockOnly: $this->lowStockOnly,
        );
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['categorySlug', 'search', 'onlyFeatured', 'sellingType', 'minPriceCents', 'maxPriceCents', 'lowStockOnly', 'sort', 'limit', 'offset'];
        $defaults = array_fill_keys($keys, null);
        $defaults['limit'] = 100;
        $defaults['offset'] = 0;
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
