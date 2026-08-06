<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Catalog\Controller\ListBrandsController;
use App\Module\Admin\UI\Catalog\Controller\ShowProductController;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Query\ProductCatalogCriteria;
use App\Module\Catalog\Application\Workflow\BrandService;
use App\Module\Catalog\Application\Workflow\CategoryCatalogWorkflow;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Persistence\CatalogPersistence;
use App\Module\Catalog\Infrastructure\Repository\BrandRepository;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\UI\Controller\PublicApi\ListCategoriesController;
use App\Module\Catalog\UI\Controller\PublicApi\ShowCategoryController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class CatalogControllerBatchTest extends TestCase
{
    public function testShowProductAndPublicCategoriesControllers(): void
    {
        $category = (new Category('Phones', 'phones'))->setDescription('Desc');
        $this->setId($category, 2);
        $product = (new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category))
            ->setShortDescription('Short')
            ->setImageName('phone.jpg')
            ->setImageAlt('Phone');
        $this->setId($product, 4);

        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls(null, $product);
        $products->expects(self::once())
            ->method('findPublished')
            ->with(self::callback(static fn (ProductCatalogCriteria $criteria): bool => 'phones' === $criteria->categorySlug && null === $criteria->search))
            ->willReturn([$product]);

        $catalogFormatter = new CatalogFormatter();
        $showProduct = new ShowProductController($products, $catalogFormatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $showProduct(404)->getStatusCode());
        $showPayload = json_decode((string) $showProduct(4)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('/uploads/products/phone.jpg', $showPayload['data']['imageUrl']);
        self::assertSame('phone.jpg', $showPayload['data']['imageName']);

        $categories = $this->createMock(CategoryRepository::class);
        $categories->expects(self::once())->method('findAllVisibleOrdered')->willReturn([$category]);
        $categories->expects(self::exactly(2))
            ->method('findOneVisibleBySlug')
            ->willReturnMap([
                ['phones', $category],
                ['missing', null],
            ]);
        $categories->method('existsWithName')->willReturn(false);
        $categories->method('existsWithSlug')->willReturn(false);

        $categoryService = new CategoryCatalogWorkflow(
            $categories,
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );

        $listCategories = new ListCategoriesController($categoryService, $catalogFormatter);
        $listPayload = json_decode((string) $listCategories()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('phones', $listPayload['data']['items'][0]['slug']);

        $showCategory = new ShowCategoryController($categoryService, new ProductQueryService($products), $catalogFormatter);
        $categoryPayload = json_decode((string) $showCategory('phones')->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Phones', $categoryPayload['data']['category']['name']);
        self::assertSame('Phone', $categoryPayload['data']['products'][0]['name']);

        self::assertSame(Response::HTTP_NOT_FOUND, $showCategory('missing')->getStatusCode());
    }

    public function testListBrandsController(): void
    {
        $brand = new Brand('Apple');
        $this->setId($brand, 9);

        $brandRepository = $this->createMock(BrandRepository::class);
        $brandRepository->expects(self::once())->method('findAllForAdmin')->willReturn([$brand]);
        $brandRepository->method('existsWithName')->willReturn(false);

        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::once())->method('countByBrand')->with($brand)->willReturn(3);

        $service = new BrandService(
            $brandRepository,
            $products,
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );

        $payload = json_decode((string) (new ListBrandsController($service, $products, new CatalogFormatter()))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Apple', $payload['data']['items'][0]['name']);
        self::assertSame(3, $payload['data']['items'][0]['productsCount']);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
