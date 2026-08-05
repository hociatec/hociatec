<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Query\ProductCatalogQuery;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class ProductCatalogSearchProvider
{
    public function __construct(
        private ProductQueryService $products,
        private CatalogCacheVersion $cacheVersion,
        #[Autowire(service: 'app.catalog_cache')]
        private CacheInterface $cache,
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
            'filters' => $criteria->filterArguments(),
            'sort' => $criteria->sort,
        ], JSON_THROW_ON_ERROR));

        $result = $this->cache->get($cacheKey, function () use ($criteria): array {
            $filters = $criteria->filterArguments();
            $items = $this->products->listPublishedProjection(
                ...[...$filters, $criteria->sort, $criteria->perPage, $criteria->offset()],
            );
            $total = $this->products->countPublished(...$filters);

            return [
                'items' => array_map(
                    static fn (array $product): array => ProductCatalogListProjectionFormatter::format($product),
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
        });

        return $result;
    }
}
