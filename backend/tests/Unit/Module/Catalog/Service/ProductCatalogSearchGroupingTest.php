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

final class ProductCatalogSearchGroupingTest extends TestCase
{
    public function testSearchGroupsVariantsBeforePagination(): void
    {
        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::exactly(2))
            ->method('findPublishedListProjection')
            ->willReturnCallback(function (ProductCatalogCriteria $criteria): array {
                self::assertSame('smartphones', $criteria->categorySlug);

                return [
                    $this->productRow(
                        id: 1,
                        name: 'iPhone 17 Pro Max reconditionne (Noir) (128 Go)',
                        slug: 'iphone-17-pro-max-noir-128',
                        sku: 'IPH17PM-128',
                        variantGroup: 'iphone-17-pro-max',
                        variantPosition: 1,
                        releaseYear: 2025,
                        color: 'Noir',
                        storage: '128 Go',
                        stock: 3,
                        priceCents: 100000,
                    ),
                    $this->productRow(
                        id: 2,
                        name: 'iPhone 17 Pro Max reconditionne (Noir) (256 Go)',
                        slug: 'iphone-17-pro-max-noir-256',
                        sku: 'IPH17PM-256',
                        variantGroup: 'iphone-17-pro-max',
                        variantPosition: 2,
                        releaseYear: 2025,
                        color: 'Noir',
                        storage: '256 Go',
                        stock: 2,
                        priceCents: 110000,
                    ),
                    $this->productRow(
                        id: 3,
                        name: 'iPhone 16 reconditionne (Bleu) (128 Go)',
                        slug: 'iphone-16-bleu-128',
                        sku: 'IPH16-128',
                        variantGroup: 'iphone-16',
                        variantPosition: 1,
                        releaseYear: 2024,
                        color: 'Bleu',
                        storage: '128 Go',
                        stock: 4,
                        priceCents: 90000,
                    ),
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
            'perPage' => 12,
            'categorySlug' => 'smartphones',
            'sort' => 'release_year_desc',
        ]));

        self::assertSame(2, $result['meta']['total']);
        self::assertSame(3, $result['meta']['variantTotal']);
        self::assertCount(2, $result['items']);
        self::assertSame('iPhone 17 Pro Max reconditionne', $result['items'][0]['modelName']);
        self::assertSame(2, $result['items'][0]['variantsCount']);
        self::assertSame(['128 Go', '256 Go'], $result['items'][0]['variantStorages']);
        self::assertSame('iPhone 16 reconditionne', $result['items'][1]['modelName']);
    }

    /**
     * @return array<string, mixed>
     */
    private function productRow(
        int $id,
        string $name,
        string $slug,
        string $sku,
        string $variantGroup,
        int $variantPosition,
        int $releaseYear,
        string $color,
        string $storage,
        int $stock,
        int $priceCents,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'shortDescription' => null,
            'description' => $name,
            'priceCents' => $priceCents,
            'sellingType' => 'sale',
            'brandId' => 9,
            'brand' => 'Apple',
            'variantGroup' => $variantGroup,
            'variantPosition' => $variantPosition,
            'releaseYear' => $releaseYear,
            'attributes' => [
                ['code' => 'storage', 'label' => 'Stockage', 'value' => $storage],
                ['code' => 'color', 'label' => 'Couleur', 'value' => $color],
            ],
            'storageCapacity' => $storage,
            'memoryRam' => null,
            'color' => $color,
            'stock' => $stock,
            'isPublished' => true,
            'isFeaturedHome' => false,
            'imageName' => null,
            'imageAlt' => $name,
            'galleryImage2Name' => null,
            'galleryImage3Name' => null,
            'galleryImage4Name' => null,
            'reviewsCount' => 0,
            'reviewsAverage' => 0.0,
            'discountEnabled' => false,
            'discountType' => null,
            'discountValue' => null,
            'discountStartsAt' => null,
            'discountEndsAt' => null,
            'createdAt' => new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
            'updatedAt' => new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
            'categoryId' => 5,
            'categoryName' => 'Smartphones',
            'categorySlug' => 'smartphones',
        ];
    }
}
