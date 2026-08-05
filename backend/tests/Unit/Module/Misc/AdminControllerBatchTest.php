<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Catalog\Controller\DeleteBrandController;
use App\Module\Admin\UI\Catalog\Controller\DeleteCategoryController;
use App\Module\Admin\UI\Catalog\Controller\ListCategoriesController;
use App\Module\Admin\UI\Catalog\Controller\ShowBrandController;
use App\Module\Admin\UI\Catalog\Controller\ShowCategoryController;
use App\Module\Admin\UI\Promotion\Controller\DeletePromotionController;
use App\Module\Admin\UI\Promotion\Controller\ListPromotionAudiencesController;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Infrastructure\Repository\BrandRepository;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use App\Module\Catalog\Application\Workflow\BrandService;
use App\Module\Catalog\Infrastructure\Persistence\CatalogPersistence;
use App\Module\Catalog\Application\Workflow\CategoryService;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\Promotion\Application\Handler\DeletePromotionHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class AdminControllerBatchTest extends TestCase
{
    public function testCatalogAdminControllersHandleShowListAndDelete(): void
    {
        $brand = new Brand('Apple');
        $this->setId($brand, 1);
        $brandRepo = $this->createMock(BrandRepository::class);
        $brandRepo->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $brand, $brand, $brand);

        $catalogFormatter = new CatalogFormatter();
        $showBrand = new ShowBrandController($brandRepo, $catalogFormatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $showBrand(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $showBrand(1)->getStatusCode());

        $missingBrandRepo = $this->createMock(BrandRepository::class);
        $missingBrandRepo->expects(self::once())->method('find')->with(404)->willReturn(null);
        $missingBrandService = new BrandService(
            $missingBrandRepo,
            $this->createMock(\App\Module\Catalog\Infrastructure\Repository\ProductRepository::class),
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );
        $deleteMissingBrand = new DeleteBrandController($missingBrandRepo, $missingBrandService);
        self::assertSame(Response::HTTP_NOT_FOUND, $deleteMissingBrand(404)->getStatusCode());

        $productRepository = $this->createMock(\App\Module\Catalog\Infrastructure\Repository\ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($brand);
        $entityManager->expects(self::once())->method('flush');
        $productRepository->expects(self::exactly(2))
            ->method('clearBrand')
            ->with($brand)
            ->willReturnCallback(static function (): void {
                static $calls = 0;
                if (0 === $calls++) {
                    throw new \RuntimeException('fail');
                }
            });
        $brandService = new BrandService($brandRepo, $productRepository, new CatalogPersistence($entityManager), Validation::createValidator());
        $deleteBrand = new DeleteBrandController($brandRepo, $brandService);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $deleteBrand(1)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $deleteBrand(1)->getStatusCode());

        $category = new Category('Phones', 'phones');
        $this->setId($category, 5);
        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects(self::exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $category);

        $showCategory = new ShowCategoryController($categoryRepo, $catalogFormatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $showCategory(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $showCategory(5)->getStatusCode());

        $busyCategory = new Category('Busy', 'busy');
        $busyCategory->addProduct($this->createMock(\App\Module\Catalog\Domain\Entity\Product::class));
        $freeCategory = new Category('Free', 'free');
        $this->setId($freeCategory, 6);
        $listRepo = $this->createMock(CategoryRepository::class);
        $listRepo->method('existsWithName')->willReturn(false);
        $listRepo->method('existsWithSlug')->willReturn(false);
        $listEntityManager = $this->createMock(EntityManagerInterface::class);
        $listEntityManager->expects(self::once())->method('remove')->with($freeCategory);
        $listEntityManager->expects(self::once())->method('flush');
        $categoryService = new CategoryService($listRepo, new CatalogPersistence($listEntityManager), Validation::createValidator());

        $missingDeleteCategoryRepo = $this->createMock(CategoryRepository::class);
        $missingDeleteCategoryRepo->expects(self::once())->method('find')->with(404)->willReturn(null);
        $deleteMissingCategory = new DeleteCategoryController($missingDeleteCategoryRepo, $categoryService);
        self::assertSame(Response::HTTP_NOT_FOUND, $deleteMissingCategory(404)->getStatusCode());

        $deleteCategoryRepo = $this->createMock(CategoryRepository::class);
        $deleteCategoryRepo->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls($busyCategory, $freeCategory);
        $deleteCategory = new DeleteCategoryController($deleteCategoryRepo, $categoryService);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $deleteCategory(5)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $deleteCategory(6)->getStatusCode());

        $listRepo2 = $this->createMock(CategoryRepository::class);
        $listRepo2->expects(self::once())->method('findAllForAdmin')->willReturn([$category]);
        $listService = new CategoryService($listRepo2, new CatalogPersistence($this->createMock(EntityManagerInterface::class)), Validation::createValidator());
        $list = new ListCategoriesController($listService, $catalogFormatter);
        $payload = json_decode((string) $list()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('phones', $payload['data']['items'][0]['slug']);
    }

    public function testPromotionAdminControllersHandleAudiencesAndDelete(): void
    {
        $promotionEngine = new PromotionEngine(
            $this->createMock(PromotionRepository::class),
            new \App\Module\Promotion\Application\Projection\PromotionFormatter(),
            new \App\Module\Promotion\Application\Provider\PromotionAudienceProvider(),
            new \App\Module\Promotion\Application\Calculator\CartSubtotalCalculator(),
            new \App\Module\Promotion\Application\Calculator\PromotionDiscountCalculator(),
            new \App\Module\Promotion\Application\Policy\PromotionEligibilityPolicy(),
        );
        $audiences = new ListPromotionAudiencesController($promotionEngine);
        $audiencesPayload = json_decode((string) $audiences()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('all_users', $audiencesPayload['data']['items']);
        self::assertSame('Tout le monde', $audiencesPayload['data']['items']['all_users']['label']);

        $promotion = new Promotion('Summer', 'summer', 'percent', 10, 'all_users');
        $this->setId($promotion, 9);
        $promotions = $this->createMock(PromotionRepository::class);
        $promotions->expects(self::exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $promotion);

        $persistenceEm = $this->createMock(EntityManagerInterface::class);
        $persistenceEm->expects(self::once())->method('remove')->with($promotion);
        $persistenceEm->expects(self::once())->method('flush');
        $deletePromotion = new DeletePromotionHandler(new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($persistenceEm));

        $controller = new DeletePromotionController($promotions, $deletePromotion);
        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $controller(9)->getStatusCode());
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
