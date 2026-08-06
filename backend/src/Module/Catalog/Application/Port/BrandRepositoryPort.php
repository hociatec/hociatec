<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Shared\Application\LockMode;

interface BrandRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Brand;

    /** @return list<Brand> */
    public function findAllForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array;

    public function countForAdmin(?string $search = null): int;

    public function existsWithName(string $name, ?int $excludeId = null): bool;

    public function findOneByName(string $name): ?Brand;
}
