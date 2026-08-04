<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Infrastructure\Repository\BrandRepository;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Workflow\BrandService;
use App\Module\Catalog\Application\Persistence\CatalogPersistence;
use App\Module\Catalog\Application\Workflow\CategoryService;
use App\Module\Training\Application\DTO\TrainingInput;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Application\Writer\TrainingWriter;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CatalogAndTrainingServicesTest extends TestCase
{
    public function testCatalogPersistenceDelegatesToEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $persistence = new CatalogPersistence($entityManager);

        $entityManager->expects(self::once())->method('persist')->with($entity);
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::once())->method('remove')->with($entity);

        $persistence->save($entity);
        $persistence->commit();
        $persistence->delete($entity);
    }

    public function testBrandServiceSupportsListCreateUpdateAndDelete(): void
    {
        $brandRepository = $this->createMock(BrandRepository::class);
        $productRepository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistence = new CatalogPersistence($entityManager);
        $validator = Validation::createValidator();
        $service = new BrandService($brandRepository, $productRepository, $persistence, $validator);

        $existingBrand = new Brand('Apple');
        $this->setId($existingBrand, 7);

        $brandRepository->expects(self::once())->method('findAllForAdmin')->willReturn([$existingBrand]);
        self::assertSame([$existingBrand], $service->listForAdmin());

        $brandRepository->expects(self::exactly(2))->method('existsWithName')->willReturn(false);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Brand::class));
        $entityManager->expects(self::exactly(3))->method('flush');
        $productRepository->expects(self::once())->method('clearBrand')->with($existingBrand);
        $entityManager->expects(self::once())->method('remove')->with($existingBrand);

        $created = $service->create('  Samsung ');
        self::assertSame('Samsung', $created->getName());

        $updated = $service->update($existingBrand, '  Apple Pro ');
        self::assertSame('Apple Pro', $updated->getName());

        $service->delete($existingBrand);
    }

    public function testBrandServiceRejectsInvalidOrDuplicateNames(): void
    {
        $brandRepository = $this->createMock(BrandRepository::class);
        $service = new BrandService(
            $brandRepository,
            $this->createMock(ProductRepository::class),
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );

        $brandRepository->method('existsWithName')->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Une marque avec ce nom existe déjà.');
        $service->create('Existing');
    }

    public function testBrandServiceRejectsBlankNameThroughValidator(): void
    {
        $service = new BrandService(
            $this->createMock(BrandRepository::class),
            $this->createMock(ProductRepository::class),
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->create('   ');
    }

    public function testCategoryServiceSupportsCrudAndValidation(): void
    {
        $repository = $this->createMock(CategoryRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistence = new CatalogPersistence($entityManager);
        $validator = Validation::createValidator();
        $service = new CategoryService($repository, $persistence, $validator);

        $category = new Category('Phones', 'phones');
        $this->setId($category, 5);

        $repository->expects(self::once())->method('findAllVisibleOrdered')->willReturn([$category]);
        $repository->expects(self::once())->method('findOneVisibleBySlug')->with('phones')->willReturn($category);
        $repository->expects(self::once())->method('findAllForAdmin')->willReturn([$category]);
        self::assertSame([$category], $service->listVisible());
        self::assertSame($category, $service->findVisibleBySlug('phones'));
        self::assertSame([$category], $service->listForAdmin());

        $repository->expects(self::exactly(2))->method('existsWithName')->willReturn(false);
        $repository->expects(self::exactly(3))->method('existsWithSlug')->willReturnOnConsecutiveCalls(true, false, false);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Category::class));
        $entityManager->expects(self::exactly(3))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with($category);

        $created = $service->create('Téléphones', null, 'Desc', true);
        self::assertSame('Téléphones', $created->getName());
        self::assertSame('telephones-2', $created->getSlug());
        self::assertSame('Desc', $created->getDescription());
        self::assertTrue($created->isVisible());

        $updated = $service->update($category, 'Phones pro', ' phones-pro ', 'Desc 2', false);
        self::assertSame('Phones pro', $updated->getName());
        self::assertSame('phones-pro', $updated->getSlug());
        self::assertSame('Desc 2', $updated->getDescription());
        self::assertFalse($updated->isVisible());

        $service->delete($category);
    }

    public function testCategoryServiceRejectsInvalidDeleteAndDuplicateSlug(): void
    {
        $repository = $this->createMock(CategoryRepository::class);
        $service = new CategoryService(
            $repository,
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );
        $category = new Category('Phones', 'phones');
        $childCategory = new Category('Child', 'child');
        $product = $this->createMock(\App\Module\Catalog\Domain\Entity\Product::class);
        $category->addProduct($product);

        try {
            $service->delete($category);
            self::fail('Expected delete exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Impossible de supprimer la categorie car elle contient encore des produits.', $exception->getMessage());
        }

        $category->removeProduct($product);
        $repository->method('existsWithName')->willReturn(false);
        $repository->method('existsWithSlug')->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ce slug est déjà utilisé. Veuillez en choisir un autre.');
        $service->update($childCategory, 'Child', 'child', null, true);
    }

    public function testCategoryServiceRejectsLongDescription(): void
    {
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('existsWithName')->willReturn(false);

        $service = new CategoryService(
            $repository,
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->create('Phones', null, str_repeat('x', 2001), true);
    }

    public function testCategoryServiceFallsBackToDefaultSlugWhenRequestedSlugNormalizesToEmptyString(): void
    {
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('existsWithName')->willReturn(false);
        $repository->method('existsWithSlug')->willReturn(false);

        $service = new CategoryService(
            $repository,
            new CatalogPersistence($this->createMock(EntityManagerInterface::class)),
            Validation::createValidator(),
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Category::class));
        $entityManager->expects(self::once())->method('flush');

        $service = new CategoryService(
            $repository,
            new CatalogPersistence($entityManager),
            Validation::createValidator(),
        );

        $created = $service->create('Phones', '***', null, true);
        self::assertSame('categorie', $created->getSlug());
    }

    public function testTrainingWriterSaveDeleteApplyAndSlugify(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistence = new DoctrineUnitOfWork($entityManager);
        $writer = new TrainingWriter($persistence);
        $training = new Training('Initial', 'initial', 30, 1000);

        $entityManager->expects(self::once())->method('persist')->with($training);
        $entityManager->expects(self::exactly(2))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with($training);

        $writer->save($training);
        $writer->delete($training);

        $input = new TrainingInput(
            ' Formation Réseau ',
            null,
            ' Court ',
            ' Objectif ',
            ' Audience ',
            ' Infra & Cloud ',
            10,
            2500,
            ['remote', 'invalid'],
            [' Intro ', ' ', ' Atelier '],
            false,
        );

        $writer->apply($training, $input);

        self::assertSame(' Formation Réseau ', $training->getTitle());
        self::assertSame('formation-reseau', $training->getSlug());
        self::assertSame('Court', $training->getShortDescription());
        self::assertSame('Objectif', $training->getObjective());
        self::assertSame('Audience', $training->getAudience());
        self::assertSame('infra-cloud', $training->getCategory());
        self::assertSame(15, $training->getDurationMinutes());
        self::assertSame(2500, $training->getPriceCents());
        self::assertSame(['remote'], $training->getAvailableFormats());
        self::assertFalse($training->isActive());
        self::assertCount(2, $training->getRoadmapItems());
        self::assertSame('Intro', $training->getRoadmapItems()->first()->getTitle());
        self::assertSame('Atelier', $training->getRoadmapItems()->last()->getTitle());

        self::assertSame('formation', $writer->slugify(' Formation '));
        self::assertSame('formation', $writer->slugify('***'));
    }

    public function testTrainingWriterFallsBackForCategoryAndFormats(): void
    {
        $writer = new TrainingWriter(new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)));
        $training = new Training('Initial', 'initial', 30, 1000);

        $writer->apply($training, TrainingInput::fromArray([
            'title' => 'Titre',
            'slug' => 'custom slug',
            'shortDescription' => '',
            'objective' => '',
            'audience' => '',
            'category' => '***',
            'durationMinutes' => 60,
            'priceCents' => 1200,
            'availableFormats' => 'invalid',
            'roadmap' => [],
            'isActive' => true,
        ]));

        self::assertSame('custom-slug', $training->getSlug());
        self::assertNull($training->getShortDescription());
        self::assertNull($training->getObjective());
        self::assertNull($training->getAudience());
        self::assertSame('general', $training->getCategory());
        self::assertSame(['onsite'], $training->getAvailableFormats());

        $writer->apply($training, TrainingInput::fromArray([
            'title' => 'Titre 2',
            'slug' => 'Été Réseau',
            'category' => ' _Infra Team_ ',
            'durationMinutes' => 90,
            'priceCents' => 2200,
            'availableFormats' => ['invalid'],
            'roadmap' => ['  '],
            'isActive' => true,
        ]));

        self::assertSame('ete-reseau', $training->getSlug());
        self::assertSame('infra-team', $training->getCategory());
        self::assertSame(['onsite'], $training->getAvailableFormats());
        self::assertCount(0, $training->getRoadmapItems());

        $writer->apply($training, new TrainingInput(
            'Titre brut',
            null,
            null,
            null,
            null,
            null,
            90,
            2200,
            [],
            [],
            true,
        ));

        self::assertSame(['onsite'], $training->getAvailableFormats());

        $writer->apply($training, TrainingInput::fromArray([
            'title' => 'Titre 3',
            'durationMinutes' => 30,
            'priceCents' => 500,
            'availableFormats' => ['remote', 'onsite', 'remote'],
            'roadmap' => ['A', 'B'],
            'isActive' => false,
        ]));

        self::assertSame(['onsite', 'remote'], $training->getAvailableFormats());
        self::assertCount(2, $training->getRoadmapItems());

        $formats = new \ReflectionMethod($writer, 'formats');
        $formats->setAccessible(true);
        self::assertSame(['onsite'], $formats->invoke($writer, 'invalid'));
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
