<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\DTO\ProductSearchCriteria;

final readonly class ProductCatalogSearchProvider
{
    public function __construct(private ProductQueryService $products)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(ProductSearchCriteria $criteria): array
    {
        $filters = $criteria->filterArguments();
        $items = $this->products->listPublished(
            ...[...$filters, $criteria->sort, $criteria->perPage, $criteria->offset()],
        );
        $total = $this->products->countPublished(...$filters);

        return [
            'items' => array_map(
                static fn ($product): array => CatalogFormatter::formatProduct($product),
                $items,
            ),
            'meta' => [
                'page' => $criteria->page,
                'perPage' => $criteria->perPage,
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $criteria->perPage)),
            ],
            'facets' => $this->products->collectPublishedFacets(...$filters),
        ];
    }
}
