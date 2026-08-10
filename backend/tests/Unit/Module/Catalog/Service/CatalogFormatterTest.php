<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Application\Projection\ProductCatalogListProjectionFormatter;
use App\Module\Catalog\Application\Provider\ProductCatalogModelAggregator;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use PHPUnit\Framework\TestCase;

final class CatalogFormatterTest extends TestCase
{
    public function testFormatCategoryIncludesCountsWhenRequested(): void
    {
        $category = new Category('Phones', 'phones');
        $category->setDescription('Smartphones');

        $product = new Product('Phone X', 'phone-x', 'SKU-1', 'Desc', 99900, 5, $category);
        $category->addProduct($product);

        $formatted = (new CatalogFormatter())->formatCategory($category, true);

        self::assertSame('Phones', $formatted['name']);
        self::assertSame('phones', $formatted['slug']);
        self::assertSame('Smartphones', $formatted['description']);
        self::assertTrue($formatted['isVisible']);
        self::assertSame(1, $formatted['productsCount']);
        self::assertIsString($formatted['createdAt']);
        self::assertIsString($formatted['updatedAt']);
    }

    public function testFormatBrandCanExposeProductCount(): void
    {
        $brand = new Brand('Apple');

        $formatted = (new CatalogFormatter())->formatBrand($brand, 12);

        self::assertSame('Apple', $formatted['name']);
        self::assertSame(12, $formatted['productsCount']);
        self::assertIsString($formatted['createdAt']);
        self::assertIsString($formatted['updatedAt']);
    }

    public function testFormatProductBuildsPublicAndPrivateFields(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $this->setEntityId($category, 10);
        $this->setEntityId($brand, 15);

        $product = new Product('iPhone 15', 'iphone-15', 'IPH-15', 'Flagship', 120000, 7, $category);
        $this->setEntityId($product, 99);
        $product
            ->setShortDescription('Short')
            ->setSellingType('rental')
            ->setBrandReference($brand)
            ->setVariantGroup('  iphone  ')
            ->setVariantPosition(2)
            ->setReleaseYear(2025)
            ->setStorageCapacity(' 256 Go ')
            ->setMemoryRam(' 8 Go ')
            ->setColor('  Noir ')
            ->setIsFeaturedHome(true)
            ->setImageName('main.jpg')
            ->setImageAlt('Main alt')
            ->setImageSize(1234)
            ->setGalleryImage2Name('side.jpg')
            ->setGalleryImage3Name('/back.jpg')
            ->setGalleryImage4Name('')
            ->setReviewsCount(4)
            ->setReviewsAverage(4.5)
            ->setDiscountEnabled(true)
            ->setDiscountType('percent')
            ->setDiscountValue(25)
            ->setDiscountStartsAt(new \DateTimeImmutable('-1 day'))
            ->setDiscountEndsAt(new \DateTimeImmutable('+1 day'));

        $formatted = (new CatalogFormatter())->formatProduct($product, true);

        self::assertSame(99, $formatted['id']);
        self::assertSame('iPhone 15', $formatted['name']);
        self::assertSame('IPH-15', $formatted['sku']);
        self::assertSame('rental', $formatted['sellingType']);
        self::assertSame('Location', $formatted['sellingTypeLabel']);
        self::assertSame('/ mois', $formatted['priceUnitLabel']);
        self::assertSame('Apple', $formatted['brand']);
        self::assertSame(15, $formatted['brandId']);
        self::assertSame('iphone', $formatted['variantGroup']);
        self::assertSame(2, $formatted['variantPosition']);
        self::assertSame(2025, $formatted['releaseYear']);
        self::assertSame('256 Go', $formatted['storageCapacity']);
        self::assertSame('8 Go', $formatted['memoryRam']);
        self::assertSame('Noir', $formatted['color']);
        self::assertSame(90000, $formatted['effectivePriceCents']);
        self::assertSame(7, $formatted['stock']);
        self::assertSame('/uploads/products/main.jpg', $formatted['imageUrl']);
        self::assertSame('Main alt', $formatted['imageAlt']);
        self::assertSame(['id' => 10, 'name' => 'Phones', 'slug' => 'phones'], $formatted['category']);
        self::assertSame(['count' => 4, 'average' => 4.5], $formatted['reviews']);
        self::assertCount(3, $formatted['gallery']);
        self::assertSame('/uploads/products/main.jpg', $formatted['gallery'][0]['url']);
        self::assertSame('/uploads/products/side.jpg', $formatted['gallery'][1]['url']);
        self::assertSame('/uploads/products/back.jpg', $formatted['gallery'][2]['url']);
        self::assertSame('main.jpg', $formatted['imageName']);
        self::assertSame(1234, $formatted['imageSize']);
        self::assertNull($formatted['imageExternalUrl']);
        self::assertSame([
            ['position' => 0, 'name' => 'main.jpg'],
            ['position' => 1, 'name' => 'side.jpg'],
            ['position' => 2, 'name' => '/back.jpg'],
            ['position' => 3, 'name' => ''],
        ], $formatted['galleryMeta']);
        self::assertSame([
            'type' => 'percent',
            'value' => 25,
            'startsAt' => $product->getDiscountStartsAt()?->format(DATE_ATOM),
            'endsAt' => $product->getDiscountEndsAt()?->format(DATE_ATOM),
            'active' => true,
        ], $formatted['discount']);
    }

    public function testFormatProductFallsBackToPrimaryImageAndSaleLabels(): void
    {
        $category = new Category('Tablets', 'tablets');
        $product = new Product('iPad', 'ipad', 'IPAD-1', 'Tablet', 50000, 3, $category);
        $product->setImageName('cover.png');

        $formatted = (new CatalogFormatter())->formatProduct($product);

        self::assertSame('Vente', $formatted['sellingTypeLabel']);
        self::assertNull($formatted['priceUnitLabel']);
        self::assertSame('/uploads/products/cover.png', $formatted['imageUrl']);
        self::assertArrayNotHasKey('discount', $formatted);
        self::assertArrayNotHasKey('imageName', $formatted);
        self::assertSame([[
            'position' => 0,
            'url' => '/uploads/products/cover.png',
            'alt' => 'iPad',
            'isPrimary' => true,
        ]], $formatted['gallery']);
    }

    public function testFormatProductUsesExternalImageAsPrimaryVisual(): void
    {
        $category = new Category('Phones', 'phones');
        $product = new Product('iPhone externe', 'iphone-externe', 'IPH-EXT', 'Phone', 70000, 4, $category);
        $product
            ->setImageName('missing-local.png')
            ->setGalleryImage2Name('side.jpg')
            ->setImageAlt('iPhone externe')
            ->setImageExternalUrl('https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg');

        $formatted = (new CatalogFormatter())->formatProduct($product, true);

        self::assertSame('https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg', $formatted['imageUrl']);
        self::assertSame('https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg', $formatted['gallery'][0]['url']);
        self::assertSame(0, $formatted['gallery'][0]['position']);
        self::assertTrue($formatted['gallery'][0]['isPrimary']);
        self::assertSame('/uploads/products/side.jpg', $formatted['gallery'][1]['url']);
        self::assertSame('https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg', $formatted['imageExternalUrl']);
    }

    public function testListProjectionUsesExternalImageAsPrimaryVisual(): void
    {
        $formatted = (new ProductCatalogListProjectionFormatter())->format([
            'id' => 1,
            'name' => 'iPhone 13',
            'slug' => 'iphone-13',
            'sku' => 'IPH-13',
            'shortDescription' => null,
            'description' => 'Phone',
            'priceCents' => 70000,
            'sellingType' => 'sale',
            'brandId' => null,
            'brand' => 'Apple',
            'variantGroup' => null,
            'variantPosition' => 1,
            'releaseYear' => 2021,
            'storageCapacity' => '256 Go',
            'memoryRam' => null,
            'color' => 'Bleu',
            'stock' => 5,
            'isPublished' => true,
            'isFeaturedHome' => true,
            'imageName' => 'missing-local.png',
            'imageAlt' => 'iPhone 13 bleu',
            'imageExternalUrl' => 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg',
            'galleryImage2Name' => 'side.jpg',
            'galleryImage3Name' => null,
            'galleryImage4Name' => null,
            'reviewsCount' => 0,
            'reviewsAverage' => 0,
            'discountEnabled' => false,
            'discountType' => null,
            'discountValue' => null,
            'discountStartsAt' => null,
            'discountEndsAt' => null,
            'createdAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
            'categoryId' => 10,
            'categoryName' => 'Phones',
            'categorySlug' => 'phones',
        ]);

        self::assertSame('https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg', $formatted['imageUrl']);
        self::assertSame('https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg', $formatted['gallery'][0]['url']);
        self::assertSame('/uploads/products/side.jpg', $formatted['gallery'][1]['url']);
    }

    public function testCatalogModelAggregatorGroupsVariantsIntoSingleModelSummary(): void
    {
        $aggregator = new ProductCatalogModelAggregator();

        $items = $aggregator->aggregate([
            [
                'id' => 2,
                'name' => 'iPhone 17 Pro Max reconditionne (Titane noir) (256 Go)',
                'slug' => 'iphone-17-pro-max-noir-256',
                'sku' => 'IPH-17-256-NOIR',
                'priceCents' => 114900,
                'discountEnabled' => false,
                'discountType' => null,
                'discountValue' => null,
                'discountStartsAt' => null,
                'discountEndsAt' => null,
                'variantGroup' => 'iphone-17-pro-max-reconditionne',
                'variantPosition' => 2,
                'color' => 'Titane noir',
                'storageCapacity' => '256 Go',
                'memoryRam' => '8 Go',
                'stock' => 3,
                'brand' => 'Apple',
                'category' => ['id' => 10, 'name' => 'Phones', 'slug' => 'phones'],
            ],
            [
                'id' => 1,
                'name' => 'iPhone 17 Pro Max reconditionne (Titane naturel) (128 Go)',
                'slug' => 'iphone-17-pro-max-naturel-128',
                'sku' => 'IPH-17-128-NAT',
                'priceCents' => 104900,
                'discountEnabled' => true,
                'discountType' => 'percent',
                'discountValue' => 10,
                'discountStartsAt' => new \DateTimeImmutable('-1 day'),
                'discountEndsAt' => new \DateTimeImmutable('+1 day'),
                'variantGroup' => 'iphone-17-pro-max-reconditionne',
                'variantPosition' => 1,
                'color' => 'Titane naturel',
                'storageCapacity' => '128 Go',
                'memoryRam' => '8 Go',
                'stock' => 2,
                'brand' => 'Apple',
                'category' => ['id' => 10, 'name' => 'Phones', 'slug' => 'phones'],
            ],
        ]);

        self::assertCount(1, $items);
        self::assertSame(1, $items[0]['id']);
        self::assertSame('iPhone 17 Pro Max reconditionne', $items[0]['modelName']);
        self::assertSame(2, $items[0]['variantsCount']);
        self::assertSame(5, $items[0]['totalStock']);
        self::assertSame(['Titane naturel', 'Titane noir'], $items[0]['variantColors']);
        self::assertSame(['128 Go', '256 Go'], $items[0]['variantStorages']);
        self::assertSame(104900, $items[0]['minVariantPriceCents']);
        self::assertSame(114900, $items[0]['maxVariantPriceCents']);
        self::assertSame(94410, $items[0]['minVariantEffectivePriceCents']);
    }

    public function testCatalogModelAggregatorBuildsGroupedFacets(): void
    {
        $aggregator = new ProductCatalogModelAggregator();

        $facets = $aggregator->collectFacets([
            [
                'id' => 1,
                'name' => 'Produit A (Noir)',
                'sku' => 'A-NOIR',
                'priceCents' => 100000,
                'discountEnabled' => false,
                'discountType' => null,
                'discountValue' => null,
                'discountStartsAt' => null,
                'discountEndsAt' => null,
                'variantGroup' => 'modele-a',
                'variantPosition' => 1,
                'color' => 'Noir',
                'storageCapacity' => '128 Go',
                'memoryRam' => '8 Go',
                'stock' => 4,
                'brand' => 'Apple',
                'category' => ['id' => 10, 'name' => 'Phones', 'slug' => 'phones'],
            ],
            [
                'id' => 2,
                'name' => 'Produit A (Bleu)',
                'sku' => 'A-BLEU',
                'priceCents' => 110000,
                'discountEnabled' => false,
                'discountType' => null,
                'discountValue' => null,
                'discountStartsAt' => null,
                'discountEndsAt' => null,
                'variantGroup' => 'modele-a',
                'variantPosition' => 2,
                'color' => 'Bleu',
                'storageCapacity' => '256 Go',
                'memoryRam' => '8 Go',
                'stock' => 2,
                'brand' => 'Apple',
                'category' => ['id' => 10, 'name' => 'Phones', 'slug' => 'phones'],
            ],
            [
                'id' => 3,
                'name' => 'Produit B',
                'sku' => 'B-1',
                'priceCents' => 90000,
                'discountEnabled' => false,
                'discountType' => null,
                'discountValue' => null,
                'discountStartsAt' => null,
                'discountEndsAt' => null,
                'variantGroup' => null,
                'variantPosition' => 1,
                'color' => 'Gris',
                'storageCapacity' => '512 Go',
                'memoryRam' => '16 Go',
                'stock' => 1,
                'brand' => 'Samsung',
                'category' => ['id' => 20, 'name' => 'Tablets', 'slug' => 'tablets'],
            ],
        ]);

        self::assertSame([
            ['value' => 'Apple', 'count' => 1, 'extra' => null],
            ['value' => 'Samsung', 'count' => 1, 'extra' => null],
        ], $facets['brands']);
        self::assertSame([
            ['value' => 'Phones', 'count' => 1, 'extra' => 'phones'],
            ['value' => 'Tablets', 'count' => 1, 'extra' => 'tablets'],
        ], $facets['categories']);
        self::assertSame(['min' => 90000, 'max' => 100000], $facets['price']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
