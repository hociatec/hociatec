<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Admin\Application\Catalog\Mapper\ProductAdminListQueryMapper;
use App\Module\Admin\UI\Catalog\Controller\ListProductsController;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProductQueryServiceTest extends TestCase
{
    public function testListAndCountForAdminDelegateAllFilters(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 2, new Category('Phones', 'phones'));

        $repository->expects(self::once())
            ->method('findAllForAdmin')
            ->with('phones', 'iphone', true, 'rental', 1000, 2000, true, 'price_desc', 25, 50)
            ->willReturn([$product]);
        $repository->expects(self::once())
            ->method('countForAdmin')
            ->with('phones', 'iphone', true, 'rental', 1000, 2000, true)
            ->willReturn(7);

        $service = new ProductQueryService($repository);

        self::assertSame([$product], $service->listForAdmin('phones', 'iphone', true, 'rental', 1000, 2000, true, 'price_desc', 25, 50));
        self::assertSame(7, $service->countForAdmin('phones', 'iphone', true, 'rental', 1000, 2000, true));
    }

    public function testAdminProductListUsesBoundedPaginationWithoutFilters(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 2, new Category('Phones', 'phones'));

        $repository->expects(self::once())
            ->method('findAllForAdmin')
            ->with(null, null, null, null, null, null, false, null, 12, 0)
            ->willReturn([$product]);
        $repository->expects(self::once())
            ->method('countForAdmin')
            ->with(null, null, null, null, null, null, false)
            ->willReturn(101);

        $controller = new ListProductsController(
            new ProductQueryService($repository),
            new ProductAdminListQueryMapper(),
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

        $repository->expects(self::once())
            ->method('findPublished')
            ->with('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true, 'newest', 12, 24)
            ->willReturn([$product]);
        $repository->expects(self::once())
            ->method('countPublished')
            ->with('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true)
            ->willReturn(3);
        $repository->expects(self::once())
            ->method('collectPublishedFacets')
            ->with('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true)
            ->willReturn(['brands' => ['Apple']]);
        $repository->expects(self::once())
            ->method('findOnePublishedBySlug')
            ->with('phone')
            ->willReturn($product);

        $service = new ProductQueryService($repository);

        self::assertSame([$product], $service->listPublished('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true, 'newest', 12, 24));
        self::assertSame(3, $service->countPublished('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true));
        self::assertSame(['brands' => ['Apple']], $service->collectPublishedFacets('phones', 'iphone', false, 'sale', 'apple', '256 Go', '8 Go', 'Noir', 1500, 3000, true));
        self::assertSame($product, $service->findPublishedBySlug('phone'));
    }
}
