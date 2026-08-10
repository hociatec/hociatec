<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\Cache\CatalogResultCache;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Query\ProductCatalogQuery;
use App\Module\Catalog\Application\Workflow\ProductQueryService;

final readonly class ProductCatalogSearchProvider
{
    public function __construct(
        private ProductQueryService $products,
        private CatalogCacheVersion $cacheVersion,
        private ProductCatalogListProjectionFormatter $formatter,
        private ProductCatalogModelAggregator $models,
        private CatalogResultCache $cache,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(ProductCatalogQuery $criteria): array
    {
        $cacheKey = 'catalog_'.hash('xxh128', (string) json_encode([
            'version' => $this->cacheVersion->current(),
            'page' => $criteria->page,
            'perPage' => $criteria->perPage,
            'criteria' => $criteria->criteria()->cacheKeyPayload(),
        ], JSON_THROW_ON_ERROR));

        $result = $this->cache->get($cacheKey, function () use ($criteria): array {
            $catalogCriteria = $criteria->criteria();
            $filterCriteria = $catalogCriteria->withoutSortAndPagination();
            $groupedItems = $this->models->aggregate(
                $this->products->listPublishedProjection($catalogCriteria->withoutPagination())
            );
            $total = count($groupedItems);
            $items = array_slice($groupedItems, $criteria->offset(), $criteria->perPage);

            return [
                'items' => array_map(
                    fn (array $product): array => $this->formatter->format($product),
                    $items,
                ),
                'meta' => [
                    'page' => $criteria->page,
                    'perPage' => $criteria->perPage,
                    'total' => $total,
                    'totalPages' => max(1, (int) ceil($total / $criteria->perPage)),
                ],
                'facets' => $this->models->collectFacets(
                    $this->products->listPublishedProjection($filterCriteria)
                ),
            ];
        });

        return $result;
    }
}
