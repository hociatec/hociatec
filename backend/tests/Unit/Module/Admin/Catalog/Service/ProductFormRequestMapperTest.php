<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Catalog\Service;

use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Catalog\Service\ProductDiscountRequestMapper;
use App\Module\Admin\Application\Catalog\Service\ProductFormRequestMapper;
use App\Module\Admin\Application\Catalog\Service\ProductGalleryRequestMapper;
use App\Module\Admin\Application\Catalog\Service\ProductVariantPayloadParser;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\BrandRepository;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProductFormRequestMapperTest extends TestCase
{
    public function testCreateMapsNormalizedPayload(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $this->setId($category, 5);
        $this->setId($brand, 9);

        $categories = $this->createMock(CategoryRepository::class);
        $brands = $this->createMock(BrandRepository::class);
        $categories->method('find')->with(5)->willReturn($category);
        $brands->method('find')->with(9)->willReturn($brand);

        $mapper = new ProductFormRequestMapper(
            $categories,
            $brands,
            new ProductVariantPayloadParser(),
            new ProductGalleryRequestMapper(),
            new ProductDiscountRequestMapper(),
        );

        $data = $mapper->create(new Request([], [
            'name' => ' iPhone ',
            'sku' => ' ip-15 ',
            'slug' => ' iphone-15 ',
            'description' => ' Description ',
            'shortDescription' => ' Short ',
            'price' => '12,50',
            'stock' => '7',
            'isPublished' => 'true',
            'isFeaturedHome' => '1',
            'categoryId' => '5',
            'imageAlt' => ' Alt ',
            'sellingType' => 'rental',
            'brandId' => '9',
            'variantGroup' => ' iphones ',
            'releaseYear' => '2025',
            'storageCapacity' => ' 256 Go ',
            'memoryRam' => ' 8 Go ',
            'color' => ' Noir ',
            'variants' => json_encode([['color' => ' Blue ', 'storageCapacity' => ' 128 Go ', 'stock' => 3]], JSON_THROW_ON_ERROR),
            'discountEnabled' => 'yes',
            'discountType' => 'fixed',
            'discountValue' => '9,99',
            'discountStartsAt' => '2026-08-01',
            'discountEndsAt' => '2026-08-31',
        ]));

        self::assertSame('iPhone', $data->name);
        self::assertSame('IP-15', $data->sku);
        self::assertSame('iphone-15', $data->slug);
        self::assertSame(1250, $data->priceCents);
        self::assertTrue($data->isPublished);
        self::assertTrue($data->isFeaturedHome);
        self::assertSame($category, $data->category);
        self::assertSame($brand, $data->brand);
        self::assertSame('iphones', $data->variantGroup);
        self::assertSame(2025, $data->releaseYear);
        self::assertSame('256 Go', $data->storageCapacity);
        self::assertSame('8 Go', $data->memoryRam);
        self::assertSame('Noir', $data->color);
        self::assertSame([['color' => 'Blue', 'storageCapacity' => '128 Go', 'stock' => 3]], $data->variantDefinitions);
        self::assertTrue($data->discountEnabled);
        self::assertSame('fixed_cents', $data->discountType);
        self::assertSame(999, $data->discountValue);
        self::assertSame('2026-08-01', $data->discountStartsAt?->format('Y-m-d'));
        self::assertSame('2026-08-31', $data->discountEndsAt?->format('Y-m-d'));
    }

    public function testUpdateUsesProductFallbacksAndGalleryRemovals(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $product = new Product('Old', 'old', 'OLD-1', 'Desc', 1000, 2, $category);
        $product->setShortDescription('Short')
            ->setImageAlt('Alt')
            ->setBrandReference($brand)
            ->setVariantGroup('group-a')
            ->setReleaseYear(2024)
            ->setStorageCapacity('128 Go')
            ->setMemoryRam('6 Go')
            ->setColor('White');
        $this->setId($category, 5);
        $this->setId($brand, 9);

        $categories = $this->createMock(CategoryRepository::class);
        $brands = $this->createMock(BrandRepository::class);
        $categories->method('find')->with(5)->willReturn($category);
        $brands->method('find')->willReturn($brand);

        $mapper = new ProductFormRequestMapper(
            $categories,
            $brands,
            new ProductVariantPayloadParser(),
            new ProductGalleryRequestMapper(),
            new ProductDiscountRequestMapper(),
        );

        $data = $mapper->update(new Request([], [
            'categoryId' => '5',
            'removeGallery' => ['0', '3', 'x'],
            'removeImage' => 'true',
        ]), $product);

        self::assertSame('Old', $data->name);
        self::assertSame('OLD-1', $data->sku);
        self::assertSame('old', $data->slug);
        self::assertSame([0, 3], $data->galleryToRemove);
        self::assertTrue($data->removeImage);
        self::assertSame('sale', $data->sellingType);
        self::assertSame($brand, $data->brand);
    }

    public function testMapperRejectsNegativePriceMissingCategoryInvalidBrandAndBadDiscountDate(): void
    {
        $categories = $this->createMock(CategoryRepository::class);
        $brands = $this->createMock(BrandRepository::class);
        $mapper = new ProductFormRequestMapper(
            $categories,
            $brands,
            new ProductVariantPayloadParser(),
            new ProductGalleryRequestMapper(),
            new ProductDiscountRequestMapper(),
        );

        try {
            $mapper->create(new Request([], ['price' => '-1', 'categoryId' => '1']));
            self::fail('Expected negative price exception.');
        } catch (ProductFormRequestException $exception) {
            self::assertSame('Le prix doit être positif.', $exception->getMessage());
            self::assertSame(422, $exception->getStatusCode());
        }

        $categories->method('find')->with(1)->willReturn(null);
        try {
            $mapper->create(new Request([], ['price' => '1', 'categoryId' => '1']));
            self::fail('Expected missing category exception.');
        } catch (ProductFormRequestException $exception) {
            self::assertSame('Catégorie introuvable.', $exception->getMessage());
            self::assertSame(404, $exception->getStatusCode());
        }

        $category = new Category('Phones', 'phones');
        $this->setId($category, 1);
        $categories = $this->createMock(CategoryRepository::class);
        $categories->method('find')->with(1)->willReturn($category);
        $brands = $this->createMock(BrandRepository::class);
        $brands->method('find')->with(99)->willReturn(null);

        $mapper = new ProductFormRequestMapper(
            $categories,
            $brands,
            new ProductVariantPayloadParser(),
            new ProductGalleryRequestMapper(),
            new ProductDiscountRequestMapper(),
        );

        try {
            $mapper->create(new Request([], ['price' => '1', 'categoryId' => '1', 'brandId' => '99']));
            self::fail('Expected missing brand exception.');
        } catch (ProductFormRequestException $exception) {
            self::assertSame('Marque introuvable.', $exception->getMessage());
            self::assertSame(404, $exception->getStatusCode());
        }

        try {
            $mapper->create(new Request([], [
                'price' => '1',
                'categoryId' => '1',
                'discountStartsAt' => 'not-a-date',
            ]));
            self::fail('Expected invalid date exception.');
        } catch (ProductFormRequestException $exception) {
            self::assertSame('Date de remise invalide.', $exception->getMessage());
            self::assertSame(400, $exception->getStatusCode());
        }
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
