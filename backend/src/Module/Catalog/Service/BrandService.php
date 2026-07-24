<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Entity\Brand;
use App\Module\Catalog\Repository\BrandRepository;
use App\Module\Catalog\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BrandService
{
    public function __construct(
        private readonly BrandRepository $brandRepository,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
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
        $this->entityManager->persist($brand);
        $this->entityManager->flush();

        return $brand;
    }

    public function update(Brand $brand, string $name): Brand
    {
        $normalizedName = $this->normalizeName($name);
        $this->assertValidName($normalizedName);
        $this->assertUniqueName($normalizedName, $brand->getId());

        $brand->setName($normalizedName);
        $this->entityManager->flush();

        return $brand;
    }

    public function delete(Brand $brand): void
    {
        $this->productRepository->clearBrand($brand);
        $this->entityManager->remove($brand);
        $this->entityManager->flush();
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
