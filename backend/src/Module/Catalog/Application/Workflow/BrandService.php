<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Port\BrandRepositoryPort;
use App\Module\Catalog\Application\Port\CatalogPersistencePort;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;

final class BrandService
{
    public function __construct(
        private readonly BrandRepositoryPort $brandRepository,
        private readonly ProductCatalogRepository $productRepository,
        private readonly CatalogPersistencePort $persistence,
    ) {
    }

    /**
     * @return list<Brand>
     */
    public function listForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        return $this->brandRepository->findAllForAdmin($limit, $offset, $search);
    }

    public function countForAdmin(?string $search = null): int
    {
        return $this->brandRepository->countForAdmin($search);
    }

    public function create(string $name): Brand
    {
        $normalizedName = $this->normalizeName($name);
        $this->assertValidName($normalizedName);
        $this->assertUniqueName($normalizedName, null);

        $brand = new Brand($normalizedName);
        try {
            $this->persistence->save($brand);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de créer la marque.', $exception);
        }

        return $brand;
    }

    public function update(Brand $brand, string $name): Brand
    {
        $normalizedName = $this->normalizeName($name);
        $this->assertValidName($normalizedName);
        $this->assertUniqueName($normalizedName, $brand->getId());

        $brand->setName($normalizedName);
        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour la marque.', $exception);
        }

        return $brand;
    }

    public function delete(Brand $brand): void
    {
        try {
            $this->productRepository->clearBrand($brand);
            $this->persistence->delete($brand);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer la marque.', $exception);
        }
    }

    private function normalizeName(string $name): string
    {
        return trim($name);
    }

    private function assertValidName(string $name): void
    {
        if ('' === $name) {
            throw new \InvalidArgumentException('La marque doit avoir un nom.');
        }
        if (mb_strlen($name) > 80) {
            throw new \InvalidArgumentException('Le nom ne doit pas dépasser 80 caractères.');
        }
    }

    private function assertUniqueName(string $name, ?int $excludeId): void
    {
        if ($this->brandRepository->existsWithName($name, $excludeId)) {
            throw new \InvalidArgumentException('Une marque avec ce nom existe déjà.');
        }
    }
}
