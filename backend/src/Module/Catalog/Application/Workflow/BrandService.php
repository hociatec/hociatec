<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Persistence\CatalogPersistence;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Module\Catalog\Infrastructure\Repository\BrandRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BrandService
{
    public function __construct(
        private readonly BrandRepository $brandRepository,
        private readonly ProductCatalogRepository $productRepository,
        private readonly CatalogPersistence $persistence,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return list<Brand>
     */
    public function listForAdmin(): array
    {
        return $this->brandRepository->findAllForAdmin();
    }

    public function create(string $name): Brand
    {
        $normalizedName = $this->normalizeName($name);
        $this->assertValidName($normalizedName);
        $this->assertUniqueName($normalizedName, null);

        $brand = new Brand($normalizedName);
        try {
            $this->persistence->save($brand);
            $this->persistence->commit();
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
            $this->persistence->commit();
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
            $this->persistence->commit();
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
        $violations = $this->validator->validate(
            ['name' => $name],
            new Assert\Collection([
                'name' => [
                    new Assert\NotBlank(message: 'La marque doit avoir un nom.'),
                    new Assert\Length(
                        max: 80,
                        maxMessage: 'Le nom ne doit pas dépasser 80 caractères.'
                    ),
                ],
            ])
        );

        if ($violations->count() > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }
    }

    private function assertUniqueName(string $name, ?int $excludeId): void
    {
        if ($this->brandRepository->existsWithName($name, $excludeId)) {
            throw new \InvalidArgumentException('Une marque avec ce nom existe déjà.');
        }
    }
}
