<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Catalog\Controller;

use App\Module\Admin\UI\Catalog\Controller\CreateBrandController;
use App\Module\Admin\UI\Catalog\Controller\CreateCategoryController;
use App\Module\Admin\UI\Catalog\Controller\UpdateBrandController;
use App\Module\Admin\UI\Catalog\Controller\UpdateCategoryController;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Infrastructure\Repository\BrandRepository;
use App\Module\Catalog\Infrastructure\Repository\CategoryRepository;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Workflow\BrandService;
use App\Module\Catalog\Infrastructure\Persistence\CatalogPersistence;
use App\Module\Catalog\Application\Workflow\CategoryService;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class AdminCatalogWriteControllersTest extends TestCase
{
    public function testBrandCreateAndUpdateControllersCoverPayloadBusinessAndSuccessBranches(): void
    {
        $brand = new Brand('Initial');
        $this->setId($brand, 5);
        $repository = $this->createMock(BrandRepository::class);
        $repository->method('existsWithName')->willReturnCallback(static fn (string $name): bool => 'Duplicate' === $name);
        $repository->method('find')->willReturnCallback(static fn (int $id): ?Brand => 5 === $id ? $brand : null);
        $service = new BrandService($repository, $this->createMock(ProductRepository::class), $this->persistence(), Validation::createValidator());
        $validator = $this->validator();

        $catalogFormatter = new CatalogFormatter();
        $create = new CreateBrandController($service, $validator, $catalogFormatter);
        self::assertSame(400, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Duplicate']))->getStatusCode());
        $created = $create($this->jsonRequest(['name' => ' Framework ']));
        self::assertSame(201, $created->getStatusCode());
        self::assertSame('Framework', $this->payload($created)['data']['name']);

        $update = new UpdateBrandController($repository, $service, $validator, $catalogFormatter);
        self::assertSame(404, $update(404, $this->jsonRequest([], 'PUT'))->getStatusCode());
        self::assertSame(400, $update(5, Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $update(5, $this->jsonRequest(['name' => 'Duplicate'], 'PUT'))->getStatusCode());
        $updated = $update(5, $this->jsonRequest(['name' => 'Updated'], 'PUT'));
        self::assertSame(200, $updated->getStatusCode());
        self::assertSame('Updated', $this->payload($updated)['data']['name']);
    }

    public function testCategoryCreateAndUpdateControllersCoverPayloadBusinessAndSuccessBranches(): void
    {
        $category = new Category('Initial', 'initial');
        $this->setId($category, 7);
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('existsWithName')->willReturnCallback(static fn (string $name): bool => 'Duplicate' === $name);
        $repository->method('existsWithSlug')->willReturnCallback(static fn (string $slug): bool => 'used' === $slug);
        $repository->method('find')->willReturnCallback(static fn (int $id): ?Category => 7 === $id ? $category : null);
        $service = new CategoryService($repository, $this->persistence(), Validation::createValidator());
        $validator = $this->validator();

        $catalogFormatter = new CatalogFormatter();
        $create = new CreateCategoryController($service, $validator, $catalogFormatter);
        self::assertSame(400, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Duplicate']))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Valid', 'slug' => 'used']))->getStatusCode());
        $created = $create($this->jsonRequest([
            'name' => ' New Category ',
            'slug' => 'new-category',
            'description' => ' Description ',
            'isVisible' => 'false',
        ]));
        self::assertSame(201, $created->getStatusCode());
        self::assertSame('new-category', $this->payload($created)['data']['slug']);
        self::assertFalse($this->payload($created)['data']['isVisible']);

        $update = new UpdateCategoryController($repository, $service, $validator, $catalogFormatter);
        self::assertSame(404, $update(404, $this->jsonRequest([], 'PUT'))->getStatusCode());
        self::assertSame(400, $update(7, Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        $updated = $update(7, $this->jsonRequest([
            'name' => 'Updated Category',
            'slug' => '',
            'description' => null,
            'isVisible' => true,
        ], 'PUT'));
        self::assertSame(200, $updated->getStatusCode());
        self::assertSame('updated-category', $this->payload($updated)['data']['slug']);
    }

    private function persistence(): CatalogPersistence
    {
        return new CatalogPersistence($this->createMock(EntityManagerInterface::class));
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
