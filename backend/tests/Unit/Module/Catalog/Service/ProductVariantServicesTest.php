<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Service\ProductCatalogRules;
use App\Module\Catalog\Application\Service\ProductVariantBatchCreator;
use App\Module\Catalog\Application\Service\ProductVariantService;
use App\Infrastructure\Persistence\DoctrinePersistence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ProductVariantServicesTest extends TestCase
{
    public function testResolveVariantGroupUsesProvidedValueOrBuildsLabelFromName(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $service = $this->service($repository);

        self::assertSame('Group A', $service->resolveVariantGroup(' Group A ', 'Phone Pro (Blue)', []));
        self::assertSame('Phone Pro', $service->resolveVariantGroup(null, 'Phone Pro (Blue) (256 Go)', []));
        self::assertSame('***', $service->resolveVariantGroup(null, '***', []));
    }

    public function testCreateVariantCopyBuildsFallbackSuffixesAndCopiesTemplateState(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $repository->expects(self::exactly(2))
            ->method('existsWithSlug')
            ->willReturnOnConsecutiveCalls(true, false);

        $service = $this->service($repository);
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $template = new Product('Phone', 'phone', 'SKU-BASE', 'Desc', 1000, 4, $category);
        $template
            ->setShortDescription('Short')
            ->setIsPublished(false)
            ->setIsFeaturedHome(true)
            ->setImageAlt('Alt')
            ->setBrandReference($brand)
            ->setReleaseYear(2026)
            ->setMemoryRam('8 Go')
            ->setSellingType('rental')
            ->setDiscountEnabled(true)
            ->setDiscountType('percent')
            ->setDiscountValue(15)
            ->setDiscountStartsAt(new \DateTimeImmutable('2026-07-01'))
            ->setDiscountEndsAt(new \DateTimeImmutable('2026-07-31'))
            ->setImageName('main.jpg')
            ->setImageSize(100)
            ->setGalleryImage2Name('g2.jpg')
            ->setGalleryImage2Size(200)
            ->setGalleryImage3Name('g3.jpg')
            ->setGalleryImage3Size(300)
            ->setGalleryImage4Name('g4.jpg')
            ->setGalleryImage4Size(400);

        $copy = $service->createVariantCopy($template, 'Phone', 'SKU-BASE', null, 'Family', null, null, 9, 2);

        self::assertSame('Phone', $copy->getName());
        self::assertSame('SKU-BASE-3', $copy->getSku());
        self::assertSame('phone-3-2', $copy->getSlug());
        self::assertSame('Family', $copy->getVariantGroup());
        self::assertSame(2, $copy->getVariantPosition());
        self::assertSame(9, $copy->getStock());
        self::assertFalse($copy->isPublished());
        self::assertTrue($copy->isFeaturedHome());
        self::assertSame($brand, $copy->getBrandReference());
        self::assertSame('main.jpg', $copy->getImageName());
        self::assertSame('g4.jpg', $copy->getGalleryImage4Name());
        self::assertSame('2026-07-31', $copy->getDiscountEndsAt()?->format('Y-m-d'));
    }

    public function testAssertDefinitionsAreUniqueRejectsExistingAndIncomingDuplicates(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $service = $this->service($repository);
        $category = new Category('Phones', 'phones');
        $current = new Product('Current', 'current', 'SKU-CUR', 'Desc', 1000, 1, $category);
        $existing = new Product('Existing', 'existing', 'SKU-EX', 'Desc', 1000, 1, $category);
        $other = new Product('Other', 'other', 'SKU-OT', 'Desc', 1000, 1, $category);
        $this->setId($current, 10);
        $this->setId($existing, 11);
        $this->setId($other, 12);
        $existing->setColor('Black')->setStorageCapacity('256 Go');
        $other->setColor('Silver')->setStorageCapacity('512 Go');

        $repository->expects(self::exactly(2))
            ->method('findByVariantGroupOrdered')
            ->with('Group')
            ->willReturn([$current, $existing, $other]);

        $service->assertDefinitionsAreUnique(null, $current, null, null, []);

        try {
            $service->assertDefinitionsAreUnique('Group', $current, 'Black', '256 Go', []);
            self::fail('Expected duplicate existing variant exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La variante Black / 256 Go existe déjà.', $exception->getMessage());
        }

        try {
            $service->assertDefinitionsAreUnique('Group', $current, 'Gold', '1 To', [
                ['color' => 'Silver', 'storageCapacity' => '512 Go'],
                ['color' => 'Silver', 'storageCapacity' => '512 Go'],
            ]);
            self::fail('Expected duplicate incoming variant exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La variante Silver / 512 Go existe déjà.', $exception->getMessage());
        }
    }

    public function testAssertDefinitionsAreUniqueIgnoresCurrentProductAndInvalidDefinitions(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $service = $this->service($repository);
        $category = new Category('Phones', 'phones');
        $current = new Product('Current', 'current', 'SKU-CUR', 'Desc', 1000, 1, $category);
        $same = new Product('Current', 'current-copy', 'SKU-COPY', 'Desc', 1000, 1, $category);
        $this->setId($current, 10);
        $this->setId($same, 10);
        $same->setColor('Blue')->setStorageCapacity('128 Go');

        $repository->expects(self::once())
            ->method('findByVariantGroupOrdered')
            ->with('Group')
            ->willReturn([$same]);

        $service->assertDefinitionsAreUnique('Group', $current, 'Blue', '128 Go', [
            'invalid',
            ['color' => ' ', 'storageCapacity' => ' '],
            ['color' => 'Red', 'storageCapacity' => '256 Go'],
        ]);

        self::assertTrue(true);
    }

    public function testVariantBatchCreatorPersistsOnlyNormalizedDefinitionsForNewAndExistingProducts(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = $this->service($repository);
        $batch = new ProductVariantBatchCreator($service, $repository, new DoctrinePersistence($entityManager));
        $category = new Category('Phones', 'phones');
        $template = new Product('Phone', 'phone', 'SKU', 'Desc', 1000, 4, $category);
        $persisted = [];

        $repository->expects(self::exactly(4))
            ->method('existsWithSlug')
            ->willReturn(false);
        $repository->expects(self::once())
            ->method('findByVariantGroupOrdered')
            ->with('Family')
            ->willReturn([$template, new Product('V1', 'v1', 'SKU-1', 'Desc', 1000, 1, $category)]);

        $entityManager->expects(self::exactly(4))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $batch->forNewProduct($template, 'Phone', 'SKU', 'phone', 'Family', 7, [
            ['color' => 'Black', 'storageCapacity' => '256 Go', 'stock' => 3],
            ['color' => '', 'storageCapacity' => '', 'stock' => 1],
            ['stock' => -1, 'color' => 'Blue'],
            'bad',
            ['color' => 'Silver'],
        ]);

        $batch->forExistingProduct($template, 'Phone', 'SKU', 'phone', '', 7, [['color' => 'Ignored']]);
        $batch->forExistingProduct($template, 'Phone', 'SKU', 'phone', 'Family', 7, [
            ['storageCapacity' => '512 Go'],
            ['color' => 'Blue', 'stock' => 5],
        ]);

        self::assertCount(4, $persisted);
        self::assertSame('Phone (Black) (256 Go)', $persisted[0]->getName());
        self::assertSame(2, $persisted[0]->getVariantPosition());
        self::assertSame(3, $persisted[0]->getStock());

        self::assertSame('Phone (Silver)', $persisted[1]->getName());
        self::assertSame(6, $persisted[1]->getVariantPosition());
        self::assertSame(7, $persisted[1]->getStock());

        self::assertSame('Phone (512 Go)', $persisted[2]->getName());
        self::assertSame(3, $persisted[2]->getVariantPosition());
        self::assertSame(7, $persisted[2]->getStock());

        self::assertSame('Phone (Blue)', $persisted[3]->getName());
        self::assertSame(4, $persisted[3]->getVariantPosition());
        self::assertSame(5, $persisted[3]->getStock());
    }

    public function testVariantServicesCoverNullConflictLabelAndEmptyExistingBatchBranch(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $service = $this->service($repository);
        $category = new Category('Phones', 'phones');
        $current = new Product('Current', 'current', 'SKU-CUR', 'Desc', 1000, 1, $category);
        $other = new Product('Other', 'other', 'SKU-OT', 'Desc', 1000, 1, $category);
        $this->setId($current, 10);
        $this->setId($other, 11);

        $repository->expects(self::once())
            ->method('findByVariantGroupOrdered')
            ->with('Group')
            ->willReturn([$other]);

        try {
            $service->assertDefinitionsAreUnique('Group', $current, null, null, []);
            self::fail('Expected duplicate null variant exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La variante cette variante existe déjà.', $exception->getMessage());
        }

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $batch = new ProductVariantBatchCreator($service, $repository, new DoctrinePersistence($entityManager));
        $batch->forExistingProduct($current, 'Phone', 'SKU', 'phone', 'Group', 3, []);
    }

    private function service(ProductRepository $repository): ProductVariantService
    {
        return new ProductVariantService(
            $repository,
            new ProductCatalogRules($repository, Validation::createValidator()),
        );
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
