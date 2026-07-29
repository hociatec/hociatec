<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Entity;

use App\Module\Catalog\Entity\Brand;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

final class CatalogEntitiesTest extends TestCase
{
    public function testBrandLifecycleAndMutators(): void
    {
        $brand = new Brand('Apple');
        $originalUpdatedAt = $brand->getUpdatedAt();

        self::assertSame('Apple', $brand->getName());

        $brand->setName('Samsung');
        self::assertSame('Samsung', $brand->getName());

        $brand->onPrePersist();
        self::assertInstanceOf(\DateTimeImmutable::class, $brand->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $brand->getUpdatedAt());

        usleep(1000);
        $brand->onPreUpdate();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $brand->getUpdatedAt());
    }

    public function testCategoryLifecycleAndProductRelation(): void
    {
        $category = new Category('Phones', 'phones');
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, $category);

        self::assertNull($category->getId());
        $category
            ->setName('Smartphones')
            ->setSlug('smartphones')
            ->setDescription('All smartphones')
            ->setIsVisible(false)
            ->addProduct($product);

        self::assertSame('Smartphones', $category->getName());
        self::assertSame('smartphones', $category->getSlug());
        self::assertSame('All smartphones', $category->getDescription());
        self::assertFalse($category->isVisible());
        self::assertCount(1, $category->getProducts());
        self::assertSame($category, $product->getCategory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Un produit ne peut pas etre retire d une categorie sans etre rattache a une autre categorie.');
        $category->removeProduct($product);
    }

    public function testCategoryCanRemoveAProductThatNoLongerBelongsToIt(): void
    {
        $category = new Category('Phones', 'phones');
        $otherCategory = new Category('Tablets', 'tablets');
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, $category);
        $category->addProduct($product);
        $product->setCategory($otherCategory);

        $category->removeProduct($product);

        self::assertCount(0, $category->getProducts());
        self::assertSame($otherCategory, $product->getCategory());
    }

    public function testRemovingUnknownProductDoesNothing(): void
    {
        $category = new Category('Phones', 'phones');
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Other', 'other'));

        $category->removeProduct($product);

        self::assertCount(0, $category->getProducts());
    }

    public function testCategoryLifecycleCallbacks(): void
    {
        $category = new Category('Phones', 'phones');

        $category->onPrePersist();
        self::assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $category->getUpdatedAt());

        $beforeUpdate = $category->getUpdatedAt();
        usleep(1000);
        $category->onPreUpdate();
        self::assertGreaterThanOrEqual($beforeUpdate, $category->getUpdatedAt());
    }

    public function testProductMutatorsDiscountsAndGallery(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $this->setEntityId($brand, 50);

        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, $category);
        $originalUpdatedAt = $product->getUpdatedAt();

        $product
            ->setName('iPhone Pro')
            ->setSlug('iphone-pro')
            ->setSku('IPH-2')
            ->setShortDescription('Short')
            ->setDescription('Long desc')
            ->setPriceCents(120000)
            ->setStock(8)
            ->setLowStockThreshold(-2)
            ->setIsPublished(false)
            ->setIsFeaturedHome(true)
            ->setSellingType('SALE')
            ->setBrandReference($brand)
            ->setVariantGroup('  premium  ')
            ->setVariantPosition(3)
            ->setReleaseYear(2024)
            ->setStorageCapacity(' 256 Go ')
            ->setMemoryRam(' 8 Go ')
            ->setColor('  Black ')
            ->setImageName('main.jpg')
            ->setImageSize(111)
            ->setImageAlt('Alt')
            ->setReviewsCount(-5)
            ->setReviewsAverage(-1.2)
            ->setDiscountEnabled(true)
            ->setDiscountType('percent')
            ->setDiscountValue(20)
            ->setDiscountStartsAt(new \DateTimeImmutable('-1 day'))
            ->setDiscountEndsAt(new \DateTimeImmutable('+1 day'))
            ->setGalleryImage2Name('second.jpg')
            ->setGalleryImage2Size(222)
            ->setGalleryImage3Name('third.jpg')
            ->setGalleryImage3Size(333)
            ->setGalleryImage4Name('fourth.jpg')
            ->setGalleryImage4Size(444);

        self::assertSame('iPhone Pro', $product->getName());
        self::assertSame('iphone-pro', $product->getSlug());
        self::assertSame('IPH-2', $product->getSku());
        self::assertSame('Short', $product->getShortDescription());
        self::assertSame('Long desc', $product->getDescription());
        self::assertSame(120000, $product->getPriceCents());
        self::assertSame(8, $product->getStock());
        self::assertSame(0, $product->getLowStockThreshold());
        self::assertFalse($product->isPublished());
        self::assertTrue($product->isFeaturedHome());
        self::assertSame('sale', $product->getSellingType());
        self::assertSame('Apple', $product->getBrand());
        self::assertSame(50, $product->getBrandId());
        self::assertSame($brand, $product->getBrandReference());
        self::assertSame('premium', $product->getVariantGroup());
        self::assertSame(3, $product->getVariantPosition());
        self::assertSame(2024, $product->getReleaseYear());
        self::assertSame('256 Go', $product->getStorageCapacity());
        self::assertSame('8 Go', $product->getMemoryRam());
        self::assertSame('Black', $product->getColor());
        self::assertSame('main.jpg', $product->getImageName());
        self::assertSame(111, $product->getImageSize());
        self::assertSame('Alt', $product->getImageAlt());
        self::assertSame(0, $product->getReviewsCount());
        self::assertSame(0.0, $product->getReviewsAverage());
        self::assertTrue($product->isDiscountEnabled());
        self::assertSame('percent', $product->getDiscountType());
        self::assertSame(20, $product->getDiscountValue());
        self::assertInstanceOf(\DateTimeImmutable::class, $product->getDiscountStartsAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $product->getDiscountEndsAt());
        self::assertSame(96000, $product->getEffectivePriceCents(new \DateTimeImmutable()));
        self::assertSame(['main.jpg', 'second.jpg', 'third.jpg', 'fourth.jpg'], $product->getGalleryImageNames());
        self::assertNull($product->getGalleryImage2File());
        self::assertSame('second.jpg', $product->getGalleryImage2Name());
        self::assertSame(222, $product->getGalleryImage2Size());
        self::assertNull($product->getGalleryImage3File());
        self::assertSame('third.jpg', $product->getGalleryImage3Name());
        self::assertSame(333, $product->getGalleryImage3Size());
        self::assertNull($product->getGalleryImage4File());
        self::assertSame('fourth.jpg', $product->getGalleryImage4Name());
        self::assertSame(444, $product->getGalleryImage4Size());
        self::assertNull($product->getGalleryImageNameByPosition(99));

        $this->assertUpdatedAtChangesOnFileOperations($product, $originalUpdatedAt);

        $product->removeGalleryImage(1)->removeGalleryImage(2)->removeGalleryImage(3)->removeGalleryImage(0);
        self::assertNull($product->getImageName());
        self::assertNull($product->getImageSize());
        self::assertNull($product->getImageAlt());
        self::assertSame([], $product->getGalleryImageNames());

        $product->setDiscountType('fixed_cents')->setDiscountValue(150000);
        self::assertSame(0, $product->getEffectivePriceCents(new \DateTimeImmutable()));

        $product->setDiscountStartsAt(new \DateTimeImmutable('+1 day'));
        self::assertSame(120000, $product->getEffectivePriceCents(new \DateTimeImmutable()));

        $product->setDiscountStartsAt(new \DateTimeImmutable('-2 day'))->setDiscountEndsAt(new \DateTimeImmutable('-1 day'));
        self::assertSame(120000, $product->getEffectivePriceCents(new \DateTimeImmutable()));

        $product->setDiscountEnabled(false);
        self::assertSame(120000, $product->getEffectivePriceCents(new \DateTimeImmutable()));

        $product
            ->setDiscountType(null)
            ->setDiscountValue(null)
            ->setDiscountStartsAt(null)
            ->setDiscountEndsAt(null)
            ->setDiscountEnabled(true)
            ->setGalleryImageFile(0, null)
            ->setGalleryImageFile(1, null)
            ->setGalleryImageFile(2, null)
            ->setGalleryImageFile(3, null);
        self::assertSame(120000, $product->getEffectivePriceCents(new \DateTimeImmutable()));
        self::assertNull($product->getDiscountType());
        self::assertNull($product->getDiscountValue());
        self::assertNull($product->getDiscountStartsAt());
        self::assertNull($product->getDiscountEndsAt());

        $product->onPrePersist();
        self::assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $product->getUpdatedAt());

        $beforeUpdate = $product->getUpdatedAt();
        usleep(1000);
        $product->onPreUpdate();
        self::assertGreaterThanOrEqual($beforeUpdate, $product->getUpdatedAt());
    }

    public function testProductRejectsInvalidValues(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setSellingType('lease');
    }

    public function testProductRejectsInvalidVariantPosition(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setVariantPosition(0);
    }

    public function testProductRejectsInvalidReleaseYear(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setReleaseYear(1999);
    }

    public function testProductRejectsInvalidCategory(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setCategory(null);
    }

    public function testProductRejectsInvalidDiscountType(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setDiscountType('bogus');
    }

    public function testProductRejectsInvalidDiscountValue(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setDiscountValue(-1);
    }

    public function testProductRejectsInvalidGalleryIndexOnSet(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->setGalleryImageFile(9, null);
    }

    public function testProductRejectsInvalidGalleryIndexOnRemove(): void
    {
        $product = new Product('iPhone', 'iphone', 'IPH-1', 'Desc', 100000, 10, new Category('Phones', 'phones'));

        $this->expectException(\InvalidArgumentException::class);
        $product->removeGalleryImage(9);
    }

    private function assertUpdatedAtChangesOnFileOperations(Product $product, \DateTimeImmutable $originalUpdatedAt): void
    {
        $path = tempnam(sys_get_temp_dir(), 'catalog-test-');
        self::assertNotFalse($path);
        file_put_contents($path, 'file');
        $file = new File($path);

        usleep(1000);
        $product->setImageFile($file);
        self::assertSame($file, $product->getImageFile());
        self::assertGreaterThan($originalUpdatedAt, $product->getUpdatedAt());

        usleep(1000);
        $product->setGalleryImageFile(1, $file);
        self::assertSame($file, $product->getGalleryImage2File());

        usleep(1000);
        $product->setGalleryImageFile(2, $file);
        self::assertSame($file, $product->getGalleryImage3File());

        usleep(1000);
        $product->setGalleryImageFile(3, $file);
        self::assertSame($file, $product->getGalleryImage4File());

        unlink($path);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
