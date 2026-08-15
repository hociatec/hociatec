<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\Cache\CatalogCacheInvalidator;
use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;
use App\Shared\Application\Text\Slugifier;
use App\Shared\Application\UnitOfWork;

class CategoryCatalogWorkflow
{
    use Slugifier;

    private CategoryRepositoryPort $categoryRepository;
    private UnitOfWork $persistence;
    private ?CatalogCacheInvalidator $cache;

    public function __construct(
        CategoryRepositoryPort $categoryRepository,
        UnitOfWork $persistence,
        mixed $cache = null,
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->persistence = $persistence;
        $this->cache = $cache instanceof CatalogCacheInvalidator ? $cache : null;
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

    public function countProductsForCategory(Category $category): int
    {
        return $this->categoryRepository->countProductsForCategory($category);
    }

    /**
     * @param list<int> $categoryIds
     *
     * @return array<int, int>
     */
    public function countProductsByCategoryIds(array $categoryIds): array
    {
        return $this->categoryRepository->countProductsByCategoryIds($categoryIds);
    }

    public function create(string $name, ?string $slug, ?string $description, bool $isVisible): Category
    {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, null);
        $attributeDefinitions = [];

        $category = new Category($name, $this->resolveSlug($slug, $name, null));
        $category
            ->setDescription($description)
            ->setIsVisible($isVisible)
            ->setAttributeDefinitions($attributeDefinitions);

        return $this->persistCreatedCategory($category);
    }

    /**
     * @param list<array{code?:mixed,label?:mixed,isRequired?:mixed,isGlobalFilter?:mixed}> $attributeDefinitions
     */
    public function createWithAttributes(
        string $name,
        ?string $slug,
        ?string $description,
        bool $isVisible,
        array $attributeDefinitions,
    ): Category {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, null);

        $category = new Category($name, $this->resolveSlug($slug, $name, null));
        $category
            ->setDescription($description)
            ->setIsVisible($isVisible)
            ->setAttributeDefinitions($this->normalizeAttributeDefinitions($attributeDefinitions));

        return $this->persistCreatedCategory($category);
    }

    private function persistCreatedCategory(Category $category): Category
    {

        try {
            $this->persistence->persist($category);
            $this->persistence->flush();
            $this->cache?->invalidateAfterWrite('category_create');
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
        array $attributeDefinitions = [],
    ): Category {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, $category->getId());

        $category
            ->setName($name)
            ->setSlug($this->resolveSlug($slug, $name, $category->getId()))
            ->setDescription($description)
            ->setIsVisible($isVisible)
            ->setAttributeDefinitions($this->normalizeAttributeDefinitions($attributeDefinitions));

        try {
            $this->persistence->flush();
            $this->cache?->invalidateAfterWrite('category_update');
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
            $this->cache?->invalidateAfterWrite('category_delete');
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

    /**
     * @param list<array{code?:mixed,label?:mixed,inputType?:mixed,helpText?:mixed,options?:mixed,isRequired?:mixed,isGlobalFilter?:mixed}> $definitions
     *
     * @return list<array{code:string,label:string,inputType:string,helpText:?string,options:list<string>,isRequired:bool,isGlobalFilter:bool}>
     */
    private function normalizeAttributeDefinitions(array $definitions): array
    {
        $normalized = [];

        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $label = is_string($definition['label'] ?? null) ? trim($definition['label']) : '';
            $requestedCode = is_string($definition['code'] ?? null) ? trim($definition['code']) : '';
            $code = $this->slugifyValue('' !== $requestedCode ? $requestedCode : $label, 'attribut');

            if ('' === $label || '' === $code) {
                continue;
            }

            $normalized[$code] = [
                'code' => $code,
                'label' => $label,
                'inputType' => $this->normalizeAttributeInputType($definition['inputType'] ?? null),
                'helpText' => $this->normalizeNullableString($definition['helpText'] ?? null),
                'options' => $this->normalizeAttributeOptions($definition['options'] ?? []),
                'isRequired' => $this->normalizeBool($definition['isRequired'] ?? false),
                'isGlobalFilter' => $this->normalizeBool($definition['isGlobalFilter'] ?? false),
            ];
        }

        return array_values($normalized);
    }

    private function normalizeAttributeInputType(mixed $value): string
    {
        $normalized = is_string($value) ? trim(mb_strtolower($value)) : '';

        return in_array($normalized, ['text', 'number', 'select', 'color', 'boolean'], true)
            ? $normalized
            : 'text';
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return '' !== $normalized ? $normalized : null;
    }

    /**
     * @return list<string>
     */
    private function normalizeAttributeOptions(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\R|,|;|\|/u', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (!is_scalar($item) && null !== $item) {
                continue;
            }

            $option = trim((string) $item);
            if ('' === $option) {
                continue;
            }

            $normalized[mb_strtolower($option)] = $option;
        }

        return array_values($normalized);
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }
}
