<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Provider;

use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\Cache\CatalogResultCache;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Application\Query\ProductCatalogQuery;
use App\Module\Catalog\Application\Workflow\CategoryCatalogWorkflow;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;

final readonly class ProductCatalogSearchProvider
{
    private const CACHE_SCHEMA_VERSION = 6;

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
            $sortedBaseProducts = $this->products->listPublishedProjection($this->withoutAttributeFilters($catalogCriteria, true));
            $facetsBaseProducts = $this->products->listPublishedProjection($this->withoutAttributeFilters($catalogCriteria, false));
            $sortedProjectedProducts = $this->filterByAttributes($sortedBaseProducts, $catalogCriteria->attributeFilters);
            $projectedProducts = $this->filterByAttributes($facetsBaseProducts, $catalogCriteria->attributeFilters);
            $groupedProducts = $this->models->aggregate($sortedProjectedProducts);
            $categoryAttributeDefinitions = $this->collectCategoryAttributeDefinitions($catalogCriteria->categorySlug);
            $total = count($groupedProducts);
            $items = array_slice($groupedProducts, $criteria->offset(), $criteria->perPage);

            return [
                'items' => array_map(
                    fn (array $product): array => $this->formatter->format($product, $catalogCriteria->sellingType),
                    $items,
                ),
                'meta' => [
                    'page' => $criteria->page,
                    'perPage' => $criteria->perPage,
                    'total' => $total,
                    'variantTotal' => count($sortedProjectedProducts),
                    'totalPages' => max(1, (int) ceil($total / $criteria->perPage)),
                ],
                'facets' => $this->formatFacets(
                    $this->collectFacets(
                        $catalogCriteria,
                        $projectedProducts,
                        $facetsBaseProducts,
                        $categoryAttributeDefinitions,
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

        return array_values(array_filter(
            $products,
            fn (array $product): bool => $this->productMatchesAttributeFilters($product, $attributeFilters),
        ));
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
     * @param array<string, mixed>  $product
     * @param array<string, string> $attributeFilters
     */
    private function productMatchesAttributeFilters(array $product, array $attributeFilters): bool
    {
        $attributes = $this->normalizeAttributes($product);
        $variantAttributes = $product['variantAttributes'] ?? null;

        foreach ($attributeFilters as $code => $selectedValue) {
            $actualValue = $attributes[$code] ?? null;

            if (is_string($actualValue) && 0 === strcasecmp($actualValue, $selectedValue)) {
                continue;
            }

            if ($this->variantAttributeContainsValue($variantAttributes, $code, $selectedValue)) {
                continue;
            }

            return false;
        }

        return true;
    }

    private function variantAttributeContainsValue(mixed $variantAttributes, string $code, string $selectedValue): bool
    {
        if (!is_array($variantAttributes)) {
            return false;
        }

        foreach ($variantAttributes as $attributeGroup) {
            if (!is_array($attributeGroup)) {
                continue;
            }

            $groupCode = trim(mb_strtolower((string) ($attributeGroup['code'] ?? '')));
            $values = $attributeGroup['values'] ?? null;

            if ($groupCode !== trim(mb_strtolower($code)) || !is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (0 === strcasecmp(trim((string) $value), $selectedValue)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>>                                                               $products
     * @param list<array<string, mixed>>                                                               $baseProducts
     * @param array<string, list<array{code:string,label:string,isRequired:bool,isGlobalFilter:bool}>> $categoryAttributeDefinitions
     *
     * @return array<string, mixed>
     */
    private function collectFacets(
        ProductCatalogCriteria $criteria,
        array $products,
        array $baseProducts,
        array $categoryAttributeDefinitions,
    ): array {
        $groupedBaseProducts = $this->models->aggregate($baseProducts);
        $groupedFacets = $this->models->collectFacets($products, $categoryAttributeDefinitions, $criteria->categorySlug);
        $allFacets = $this->models->collectFacets($baseProducts, $categoryAttributeDefinitions, $criteria->categorySlug);
        $facets = $groupedFacets;
        $allAttributeFacets = $allFacets['attributes'] ?? [];

        if (!is_array($allAttributeFacets) || [] === $allAttributeFacets) {
            return $facets;
        }

        $relaxedAttributeFacets = [];

        foreach ($allAttributeFacets as $attributeFacet) {
            if (!is_array($attributeFacet) || !is_string($attributeFacet['code'] ?? null)) {
                continue;
            }

            $code = trim($attributeFacet['code']);
            if ('' === $code) {
                continue;
            }

            $filters = $criteria->attributeFilters;
            unset($filters[$code]);

            $matchingProducts = $this->filterByAttributes($groupedBaseProducts, $filters);
            $matchingFacets = $this->models->collectFacets(
                $matchingProducts,
                $categoryAttributeDefinitions,
                $criteria->categorySlug,
            )['attributes'] ?? [];

            if (!is_array($matchingFacets)) {
                continue;
            }

            foreach ($matchingFacets as $matchingFacet) {
                if (is_array($matchingFacet) && ($matchingFacet['code'] ?? null) === $code) {
                    $relaxedAttributeFacets[] = $matchingFacet;
                    break;
                }
            }
        }

        $facets['attributes'] = $relaxedAttributeFacets;

        return $facets;
    }

    private function withoutAttributeFilters(ProductCatalogCriteria $criteria, bool $keepSort): ProductCatalogCriteria
    {
        return new ProductCatalogCriteria([
            'categorySlug' => $criteria->categorySlug,
            'search' => $criteria->search,
            'onlyFeatured' => $criteria->onlyFeatured,
            'sellingType' => $criteria->sellingType,
            'brand' => $criteria->brand,
            'attributeFilters' => [],
            'storageCapacity' => null,
            'memoryRam' => null,
            'color' => null,
            'minPriceCents' => $criteria->minPriceCents,
            'maxPriceCents' => $criteria->maxPriceCents,
            'inStockOnly' => $criteria->inStockOnly,
            'sort' => $keepSort ? $criteria->sort : null,
            'limit' => null,
            'offset' => null,
        ]);
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
        return [
            'brands' => $facets['brands'] ?? [],
            'categories' => $facets['categories'] ?? [],
            'attributes' => is_array($facets['attributes'] ?? null) ? $facets['attributes'] : [],
            'price' => $facets['price'] ?? ['min' => null, 'max' => null],
        ];
    }
}
