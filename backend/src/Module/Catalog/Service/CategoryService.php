<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Exception\CatalogOperationException;
use App\Module\Catalog\Repository\CategoryRepository;
use App\Shared\Service\Slugifier;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CategoryService
{
    use Slugifier;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CatalogPersistence $persistence,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return list<Category>
     */
    public function listVisible(): array
    {
        return $this->categoryRepository->findAllVisibleOrdered();
    }

    public function findVisibleBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findOneVisibleBySlug($slug);
    }

    /**
     * @return list<Category>
     */
    public function listForAdmin(): array
    {
        return $this->categoryRepository->findAllForAdmin();
    }

    public function create(string $name, ?string $slug, ?string $description, bool $isVisible): Category
    {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, null);

        $resolvedSlug = $this->resolveSlug($slug, $name, null);

        $category = new Category($name, $resolvedSlug);
        $category
            ->setDescription($description)
            ->setIsVisible($isVisible);

        try {
            $this->persistence->save($category);
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de créer la catégorie.', $exception);
        }

        return $category;
    }

    public function update(
        Category $category,
        string $name,
        ?string $slug,
        ?string $description,
        bool $isVisible,
    ): Category {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, $category->getId());

        $resolvedSlug = $this->resolveSlug($slug, $name, $category->getId());

        $category
            ->setName($name)
            ->setSlug($resolvedSlug)
            ->setDescription($description)
            ->setIsVisible($isVisible);

        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour la catégorie.', $exception);
        }

        return $category;
    }

    public function delete(Category $category): void
    {
        if (!$category->getProducts()->isEmpty()) {
            throw CatalogOperationException::invalidOperation('Impossible de supprimer la categorie car elle contient encore des produits.');
        }

        try {
            $this->persistence->delete($category);
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer la catégorie.', $exception);
        }
    }

    private function generateUniqueSlug(string $name, ?int $excludeId): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $attempt = 1;

        while ($this->categoryRepository->existsWithSlug($slug, $excludeId)) {
            ++$attempt;
            $slug = sprintf('%s-%d', $baseSlug, $attempt);
        }

        return $slug;
    }

    private function assertUniqueName(string $name, ?int $excludeId): void
    {
        if ($this->categoryRepository->existsWithName($name, $excludeId)) {
            throw new \InvalidArgumentException('Une categorie avec ce nom existe déjà.');
        }
    }

    private function resolveSlug(?string $requestedSlug, string $name, ?int $excludeId): string
    {
        if (null !== $requestedSlug && '' !== trim($requestedSlug)) {
            $normalized = $this->slugify($requestedSlug);

            if ('' === $normalized) {
                throw new \InvalidArgumentException('Le slug fourni est invalide.');
            }

            if ($this->categoryRepository->existsWithSlug($normalized, $excludeId)) {
                throw new \InvalidArgumentException('Ce slug est déjà utilisé. Veuillez en choisir un autre.');
            }

            return $normalized;
        }

        return $this->generateUniqueSlug($name, $excludeId);
    }

    private function assertValidData(string $name, ?string $description): void
    {
        $violations = $this->validator->validate(
            [
                'name' => $name,
                'description' => $description,
            ],
            new Assert\Collection([
                'name' => [
                    new Assert\NotBlank(message: 'La categorie doit avoir un nom.'),
                    new Assert\Length(
                        max: 150,
                        maxMessage: 'Le nom ne doit pas depasser 150 caracteres.'
                    ),
                ],
                'description' => [
                    new Assert\Optional([
                        new Assert\Length(
                            max: 2000,
                            maxMessage: 'La description est trop longue.'
                        ),
                    ]),
                ],
            ])
        );

        if ($violations->count() > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }
    }

    private function slugify(string $value): string
    {
        return $this->slugifyValue($value, 'categorie');
    }
}
