<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Application\UnitOfWork;
use App\Shared\Application\Text\Slugifier;

readonly class CategoryCatalogWorkflow
{
    use Slugifier;

    public function __construct(
        private CategoryRepositoryPort $categoryRepository,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @return list<Category>
     */
    public function listVisible(int $limit = 50, int $offset = 0): array
    {
        return $this->categoryRepository->findAllVisibleOrdered($limit, $offset);
    }

    public function countVisible(): int
    {
        return $this->categoryRepository->countVisible();
    }

    public function findVisibleBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findOneVisibleBySlug($slug);
    }

    /**
     * @return list<Category>
     */
    public function listForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        return $this->categoryRepository->findAllForAdmin($limit, $offset, $search);
    }

    public function countForAdmin(?string $search = null): int
    {
        return $this->categoryRepository->countForAdmin($search);
    }

    public function create(string $name, ?string $slug, ?string $description, bool $isVisible): Category
    {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, null);

        $category = new Category($name, $this->resolveSlug($slug, $name, null));
        $category
            ->setDescription($description)
            ->setIsVisible($isVisible);

        try {
            $this->persistence->persist($category);
            $this->persistence->flush();
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

        $category
            ->setName($name)
            ->setSlug($this->resolveSlug($slug, $name, $category->getId()))
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
            $this->persistence->remove($category);
            $this->persistence->flush();
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
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('La categorie doit avoir un nom.');
        }
        if (mb_strlen($name) > 150) {
            throw new \InvalidArgumentException('Le nom ne doit pas depasser 150 caracteres.');
        }
        if (null !== $description && mb_strlen($description) > 2000) {
            throw new \InvalidArgumentException('La description est trop longue.');
        }
    }

    private function slugify(string $value): string
    {
        return $this->slugifyValue($value, 'categorie');
    }
}
