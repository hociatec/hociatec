<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Domain\Entity\Category;
use App\Shared\Application\LockMode;

interface CategoryRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Category;

    /** @return list<Category> */
    public function findAllVisibleOrdered(int $limit = 50, int $offset = 0): array;

    public function countVisible(): int;

    /** @return list<Category> */
    public function findAllForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array;

    public function countForAdmin(?string $search = null): int;

    public function countProductsForCategory(Category $category): int;

    /**
     * @param list<int> $categoryIds
     *
     * @return array<int, int>
     */
    public function countProductsByCategoryIds(array $categoryIds): array;

    public function findOneVisibleBySlug(string $slug): ?Category;

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool;

    public function existsWithName(string $name, ?int $excludeId = null): bool;
}
