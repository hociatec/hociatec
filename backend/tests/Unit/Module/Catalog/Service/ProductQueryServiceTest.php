<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Admin\Application\Catalog\Mapper\ProductAdminListQueryMapper;
use App\Module\Admin\UI\Catalog\Controller\ListProductsController;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Query\ProductAdminCriteria;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProductQueryServiceTest extends TestCase
{
    public function testListAndCountForAdminDelegateAllFilters(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 2, new Category('Phones', 'phones'));
        $criteria = new ProductAdminCriteria('phones', 'iphone', true, 'rental', 1000, 2000, true, 'price_desc', 25, 50);

        $repository->expects(self::once())
            ->method('findAllForAdmin')
            ->with(self::callback(static fn (ProductAdminCriteria $actual): bool => $actual === $criteria))
            ->willReturn([$product]);
        $repository->expects(self::once())
            ->method('countForAdmin')
            ->with(self::callback(static fn (ProductAdminCriteria $actual): bool => null === $actual->sort && 100 === $actual->limit && 0 === $actual->offset))
            ->willReturn(7);

        $service = new ProductQueryService($repository);

        self::assertSame([$product], $service->listForAdmin($criteria));
        self::assertSame(7, $service->countForAdmin($criteria->withoutSortAndPagination()));
    }

    public function testAdminProductListUsesBoundedPaginationWithoutFilters(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 2, new Category('Phones', 'phones'));

        $repository->expects(self::once())
            ->method('findAllForAdmin')
            ->with(self::callback(static fn (ProductAdminCriteria $criteria): bool => 12 === $criteria->limit && 0 === $criteria->offset))
            ->willReturn([$product]);
        $repository->expects(self::once())
            ->method('countForAdmin')
            ->with(self::isInstanceOf(ProductAdminCriteria::class))
            ->willReturn(101);

        $controller = new ListProductsController(
            new ProductQueryService($repository),
            new ProductAdminListQueryMapper(),
            new CatalogFormatter(),
        );

        $payload = json_decode((string) $controller(new Request())->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $payload['data']['meta']['page']);
        self::assertSame(12, $payload['data']['meta']['perPage']);
        self::assertSame(101, $payload['data']['meta']['total']);
        self::assertSame(9, $payload['data']['meta']['totalPages']);
    }

    public function testPublishedQueriesDelegateFiltersFacetsAndSlugLookup(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 2, new Category('Phones', 'phones'));
        $criteria = new ProductCatalogCriteria('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true, 'newest', 12, 24);

        $repository->expects(self::once())
            ->method('findPublished')
            ->with(self::callback(static fn (ProductCatalogCriteria $actual): bool => $actual === $criteria))
            ->willReturn([$product]);
        $repository->expects(self::once())
            ->method('countPublished')
            ->with(self::callback(static fn (ProductCatalogCriteria $actual): bool => null === $actual->sort && null === $actual->limit && null === $actual->offset))
            ->willReturn(3);
        $repository->expects(self::once())
            ->method('collectPublishedFacets')
            ->with(self::callback(static fn (ProductCatalogCriteria $actual): bool => null === $actual->sort && null === $actual->limit && null === $actual->offset))
            ->willReturn(['brands' => ['Apple']]);
        $repository->expects(self::once())
            ->method('findOnePublishedBySlug')
            ->with('phone')
            ->willReturn($product);

        $service = new ProductQueryService($repository);

        self::assertSame([$product], $service->listPublished($criteria));
        self::assertSame(3, $service->countPublished($criteria->withoutSortAndPagination()));
        self::assertSame(['brands' => ['Apple']], $service->collectPublishedFacets($criteria->withoutSortAndPagination()));
        self::assertSame($product, $service->findPublishedBySlug('phone'));
    }
}
