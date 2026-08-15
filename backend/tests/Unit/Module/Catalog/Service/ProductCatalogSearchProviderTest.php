<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Provider\ProductCatalogModelAggregator;
use App\Module\Catalog\Application\Provider\ProductCatalogSearchProvider;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Application\Query\ProductCatalogQuery;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Infrastructure\Cache\SymfonyCatalogResultCache;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class ProductCatalogSearchProviderTest extends TestCase
{
    public function testAttributeFacetRemainsActionableWhenAnotherSelectedAttributeIsInvalid(): void
    {
        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::exactly(2))
            ->method('findPublishedListProjection')
            ->willReturnCallback(function (ProductCatalogCriteria $criteria): array {
                self::assertSame('smartphones', $criteria->categorySlug);
                self::assertSame([], $criteria->attributeFilters);
                self::assertNull($criteria->storageCapacity);
                self::assertNull($criteria->memoryRam);
                self::assertNull($criteria->color);

                return [
                    [
                        'id' => 1,
                        'name' => 'Phone A',
                        'slug' => 'phone-a',
                        'sku' => 'PHONE-A-128',
                        'description' => 'Phone A',
                        'priceCents' => 100000,
                        'sellingType' => 'sale',
                        'stock' => 3,
                        'brand' => 'Apple',
                        'category' => ['id' => 10, 'name' => 'Smartphones', 'slug' => 'smartphones'],
                        'attributes' => [
                            ['code' => 'color', 'label' => 'Couleur', 'value' => 'Noir'],
                            ['code' => 'storage', 'label' => 'Stockage', 'value' => '128 Go'],
                        ],
                        'color' => 'Noir',
                        'storageCapacity' => '128 Go',
                        'createdAt' => new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
                        'updatedAt' => new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
                        'categoryId' => 10,
                        'categoryName' => 'Smartphones',
                        'categorySlug' => 'smartphones',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Phone A',
                        'slug' => 'phone-a-256',
                        'sku' => 'PHONE-A-256',
                        'description' => 'Phone A',
                        'priceCents' => 120000,
                        'sellingType' => 'sale',
                        'stock' => 4,
                        'brand' => 'Apple',
                        'category' => ['id' => 10, 'name' => 'Smartphones', 'slug' => 'smartphones'],
                        'attributes' => [
                            ['code' => 'color', 'label' => 'Couleur', 'value' => 'Noir'],
                            ['code' => 'storage', 'label' => 'Stockage', 'value' => '256 Go'],
                        ],
                        'color' => 'Noir',
                        'storageCapacity' => '256 Go',
                        'createdAt' => new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
                        'updatedAt' => new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
                        'categoryId' => 10,
                        'categoryName' => 'Smartphones',
                        'categorySlug' => 'smartphones',
                    ],
                ];
            });

        $provider = new ProductCatalogSearchProvider(
            new ProductQueryService($products),
            new CatalogCacheVersion(new ArrayAdapter(), new NullLogger()),
            new ProductCatalogListProjectionFormatter(),
            new ProductCatalogModelAggregator(),
            new SymfonyCatalogResultCache(new ArrayAdapter()),
        );

        $result = $provider->search(new ProductCatalogQuery([
            'page' => 1,
            'perPage' => 10,
            'categorySlug' => 'smartphones',
            'attributeFilters' => [
                'color' => 'Noir',
                'storage' => '512 Go',
            ],
            'sort' => 'release_year_desc',
        ]));

        self::assertSame(0, $result['meta']['total']);
        self::assertSame([], $result['items']);
        $storageFacet = null;

        foreach ($result['facets']['attributes'] ?? [] as $attributeFacet) {
            if (is_array($attributeFacet) && ($attributeFacet['code'] ?? null) === 'storage') {
                $storageFacet = $attributeFacet;
                break;
            }
        }

        self::assertSame([
            [
                'value' => '128 Go',
                'count' => 1,
                'extra' => null,
            ],
            [
                'value' => '256 Go',
                'count' => 1,
                'extra' => null,
            ],
        ], $storageFacet['values'] ?? null);
    }
}
