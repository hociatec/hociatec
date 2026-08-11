<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Application\Cache\CatalogCacheVersion;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Provider\ProductCatalogModelAggregator;
use App\Module\Catalog\Application\Provider\ProductCatalogSearchProvider;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Cache\SymfonyCatalogResultCache;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\UI\Controller\PublicApi\ListProductsController;
use App\Module\Catalog\UI\Http\ProductSearchRequestMapper;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;

final class CatalogRequestAndProductsControllerTest extends MiscSupportTestCase
{
    #[Test]
    public function catalogRequestMapperAndListProductsController(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $this->setId($category, 5);
        $this->setId($brand, 9);

        $product = (new Product('iPhone', 'iphone', 'IP-1', 'Desc', 199900, 4, $category))
            ->setShortDescription('Short')
            ->setSellingType('rental')
            ->setBrandReference($brand)
            ->setStorageCapacity('256 Go')
            ->setMemoryRam('8 Go')
            ->setColor('Noir')
            ->setImageName('iphone.jpg')
            ->setImageAlt('iPhone');
        $this->setId($product, 12);

        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::exactly(2))
            ->method('findPublishedListProjection')
            ->with(self::callback(static fn (ProductCatalogCriteria $criteria): bool => 'phones' === $criteria->categorySlug
                && 'iphone' === $criteria->search
                && true === $criteria->onlyFeatured
                && 'rental' === $criteria->sellingType
                && 'apple' === $criteria->brand
                && '256 Go' === $criteria->storageCapacity
                && '8 Go' === $criteria->memoryRam
                && 'Noir' === $criteria->color
                && 1050 === $criteria->minPriceCents
                && 2000 === $criteria->maxPriceCents
                && true === $criteria->inStockOnly
                && (
                    ('price_desc' === $criteria->sort && null === $criteria->limit && null === $criteria->offset)
                    || (null === $criteria->sort && null === $criteria->limit && null === $criteria->offset)
                )))
            ->willReturn([[
                'id' => 12,
                'name' => 'iPhone',
                'slug' => 'iphone',
                'sku' => 'IP-1',
                'shortDescription' => 'Short',
                'description' => 'Desc',
                'priceCents' => 199900,
                'sellingType' => 'rental',
                'brandId' => 9,
                'brand' => 'Apple',
                'variantGroup' => null,
                'variantPosition' => 0,
                'releaseYear' => null,
                'storageCapacity' => '256 Go',
                'memoryRam' => '8 Go',
                'color' => 'Noir',
                'stock' => 4,
                'isPublished' => false,
                'isFeaturedHome' => false,
                'imageName' => 'iphone.jpg',
                'imageAlt' => 'iPhone',
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
                'createdAt' => new \DateTimeImmutable('2026-07-01T10:00:00+00:00'),
                'updatedAt' => new \DateTimeImmutable('2026-07-02T10:00:00+00:00'),
                'categoryId' => 5,
                'categoryName' => 'Phones',
                'categorySlug' => 'phones',
            ]]);
        $products->expects(self::never())->method('countPublished');
        $products->expects(self::never())->method('collectPublishedFacets');

        $cache = new ArrayAdapter();
        $controller = new ListProductsController(
            new ProductSearchRequestMapper(),
            new ProductCatalogSearchProvider(
                new ProductQueryService($products),
                new CatalogCacheVersion($cache, new NullLogger()),
                new ProductCatalogListProjectionFormatter(),
                new ProductCatalogModelAggregator(),
                new SymfonyCatalogResultCache($cache),
            ),
        );

        $request = new Request([
            'page' => '0',
            'perPage' => '100',
            'category' => ' phones ',
            'q' => ' iphone ',
            'homepage' => 'yes',
            'sellingType' => 'RENTAL',
            'brand' => ' apple ',
            'storageCapacity' => '256 Go',
            'memoryRam' => '8 Go',
            'color' => 'Noir',
            'minPrice' => '10.50',
            'maxPrice' => '20',
            'inStock' => 'true',
            'sort' => 'price_desc',
        ]);

        $payload = json_decode((string) $controller($request)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $payload['data']['meta']['page']);
        self::assertSame(48, $payload['data']['meta']['perPage']);
        self::assertSame(1, $payload['data']['meta']['total']);
        self::assertSame(1, $payload['data']['meta']['totalPages']);
        self::assertSame('Location', $payload['data']['items'][0]['sellingTypeLabel']);
        self::assertSame('Apple', $payload['data']['items'][0]['brand']);
        self::assertSame('/uploads/products/iphone.jpg', $payload['data']['items'][0]['imageUrl']);
        self::assertSame([
            'brands' => [['value' => 'Apple', 'count' => 1, 'extra' => null]],
            'categories' => [['value' => 'Phones', 'count' => 1, 'extra' => 'phones']],
            'storageCapacities' => [['value' => '256 Go', 'count' => 1, 'extra' => null]],
            'memoryRams' => [['value' => '8 Go', 'count' => 1, 'extra' => null]],
            'colors' => [['value' => 'Noir', 'count' => 1, 'extra' => null]],
            'price' => ['min' => 199900, 'max' => 199900],
        ], $payload['data']['facets']);

        $criteria = (new ProductSearchRequestMapper())->map(new Request([
            'page' => '-4',
            'perPage' => '0',
            'category' => '   ',
            'q' => null,
            'homepage' => '0',
            'sellingType' => 'invalid',
            'brand' => 123,
            'storageCapacity' => '',
            'memoryRam' => false,
            'color' => '   ',
            'minPrice' => '-5',
            'maxPrice' => 'oops',
            'inStock' => 0,
            'sort' => 'weird',
        ]));
        self::assertSame(1, $criteria->page);
        self::assertSame(1, $criteria->perPage);
        self::assertNull($criteria->categorySlug);
        self::assertNull($criteria->query);
        self::assertNull($criteria->homepageOnly);
        self::assertNull($criteria->sellingType);
        self::assertNull($criteria->brandSlug);
        self::assertNull($criteria->storageCapacity);
        self::assertNull($criteria->memoryRam);
        self::assertNull($criteria->color);
        self::assertSame(0, $criteria->minPriceCents);
        self::assertNull($criteria->maxPriceCents);
        self::assertFalse($criteria->inStockOnly);
        self::assertNull($criteria->sort);

        $criteria = (new ProductSearchRequestMapper())->map(new Request([
            'minPrice' => '',
            'maxPrice' => '',
        ]));
        self::assertNull($criteria->minPriceCents);
        self::assertNull($criteria->maxPriceCents);
    }
}
