<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Domain\Entity\Category;
use App\Shared\Application\LockMode;

interface CategoryRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Category;

    /** @return list<Category> */
    public function findAllVisibleOrdered(): array;

    /** @return list<Category> */
    public function findAllForAdmin(): array;

    public function findOneVisibleBySlug(string $slug): ?Category;

    public function existsWithSlug(string $slug, ?int $excludeId = null): bool;

    public function existsWithName(string $name, ?int $excludeId = null): bool;
}
