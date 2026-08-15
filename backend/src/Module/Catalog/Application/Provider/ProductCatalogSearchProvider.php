<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\Cache\CatalogResultCache;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Query\ProductCatalogQuery;
use App\Module\Catalog\Application\Workflow\CategoryCatalogWorkflow;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;

final readonly class ProductCatalogSearchProvider
{
    private const CACHE_SCHEMA_VERSION = 3;

    public function __construct(
        private ProductQueryService $products,
        private CatalogCacheVersion $cacheVersion,
        private ProductCatalogListProjectionFormatter $formatter,
        private ProductCatalogModelAggregator $models,
        private CatalogResultCache $cache,
        private ?CategoryCatalogWorkflow $categories = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(ProductCatalogQuery $criteria): array
    {
        $cacheKey = 'catalog_'.hash('xxh128', (string) json_encode([
            'schema' => self::CACHE_SCHEMA_VERSION,
            'version' => $this->cacheVersion->current(),
            'page' => $criteria->page,
            'perPage' => $criteria->perPage,
            'criteria' => $criteria->criteria()->cacheKeyPayload(),
        ], JSON_THROW_ON_ERROR));

        $result = $this->cache->get($cacheKey, function () use ($criteria): array {
            $catalogCriteria = $criteria->criteria();
            $sortedProjectedProducts = $this->filterByAttributes(
                $this->products->listPublishedProjection($catalogCriteria->withoutPagination()),
                $catalogCriteria->attributeFilters,
            );
            $projectedProducts = $this->filterByAttributes(
                $this->products->listPublishedProjection($catalogCriteria->withoutSortAndPagination()),
                $catalogCriteria->attributeFilters,
            );
            $categoryAttributeDefinitions = $this->collectCategoryAttributeDefinitions($catalogCriteria->categorySlug);
            $total = count($sortedProjectedProducts);
            $items = array_slice($sortedProjectedProducts, $criteria->offset(), $criteria->perPage);

            return [
                'items' => array_map(
                    fn (array $product): array => $this->formatter->format($product, $catalogCriteria->sellingType),
                    $items,
                ),
                'meta' => [
                    'page' => $criteria->page,
                    'perPage' => $criteria->perPage,
                    'total' => $total,
                    'totalPages' => max(1, (int) ceil($total / $criteria->perPage)),
                ],
                'facets' => $this->formatFacets(
                    $this->models->collectRawFacets(
                        $projectedProducts,
                        $categoryAttributeDefinitions,
                        $catalogCriteria->categorySlug,
                    ),
                ),
            ];
        });

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $products
     * @param array<string, string>      $attributeFilters
     *
     * @return list<array<string, mixed>>
     */
    private function filterByAttributes(array $products, array $attributeFilters): array
    {
        if ([] === $attributeFilters) {
            return $products;
        }

        return array_values(array_filter($products, function (array $product) use ($attributeFilters): bool {
            $attributes = $this->normalizeAttributes($product);

            foreach ($attributeFilters as $code => $selectedValue) {
                $actualValue = $attributes[$code] ?? null;

                if (null === $actualValue || 0 !== strcasecmp($actualValue, $selectedValue)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, string>
     */
    private function normalizeAttributes(array $product): array
    {
        $normalized = [];
        $attributes = $product['attributes'] ?? null;

        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $code = trim(mb_strtolower((string) ($attribute['code'] ?? '')));
                $value = trim((string) ($attribute['value'] ?? ''));

                if ('' === $code || '' === $value) {
                    continue;
                }

                $normalized[$code] = $value;
            }
        }

        foreach ([
            LegacyProductAttribute::STORAGE_CODE => isset($product['storageCapacity']) ? trim((string) $product['storageCapacity']) : '',
            LegacyProductAttribute::MEMORY_RAM_CODE => isset($product['memoryRam']) ? trim((string) $product['memoryRam']) : '',
            LegacyProductAttribute::COLOR_CODE => isset($product['color']) ? trim((string) $product['color']) : '',
        ] as $code => $value) {
            if ('' !== $value && !isset($normalized[$code])) {
                $normalized[$code] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, list<array{code:string,label:string,isRequired:bool,isGlobalFilter:bool}>>
     */
    private function collectCategoryAttributeDefinitions(?string $selectedCategorySlug): array
    {
        if (!$this->categories instanceof CategoryCatalogWorkflow) {
            return [];
        }

        if (null !== $selectedCategorySlug) {
            $category = $this->categories->findVisibleBySlug($selectedCategorySlug);

            return null === $category
                ? []
                : [$selectedCategorySlug => $category->getAttributeDefinitions()];
        }

        $definitions = [];

        foreach ($this->categories->listVisible(100, 0) as $category) {
            $slug = trim($category->getSlug());

            if ('' === $slug) {
                continue;
            }

            $definitions[$slug] = $category->getAttributeDefinitions();
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $facets
     *
     * @return array<string, mixed>
     */
    private function formatFacets(array $facets): array
    {
        if ($this->categories instanceof CategoryCatalogWorkflow) {
            return $facets;
        }

        $legacy = [
            'brands' => $facets['brands'] ?? [],
            'categories' => $facets['categories'] ?? [],
            'storageCapacities' => [],
            'memoryRams' => [],
            'colors' => [],
            'price' => $facets['price'] ?? ['min' => null, 'max' => null],
        ];

        foreach (($facets['attributes'] ?? []) as $attributeFacet) {
            if (!is_array($attributeFacet)) {
                continue;
            }

            $code = trim((string) ($attributeFacet['code'] ?? ''));
            $values = is_array($attributeFacet['values'] ?? null) ? $attributeFacet['values'] : [];

            if (LegacyProductAttribute::STORAGE_CODE === $code) {
                $legacy['storageCapacities'] = $values;
            } elseif (LegacyProductAttribute::MEMORY_RAM_CODE === $code) {
                $legacy['memoryRams'] = $values;
            } elseif (LegacyProductAttribute::COLOR_CODE === $code) {
                $legacy['colors'] = $values;
            }
        }

        return $legacy;
    }
}
