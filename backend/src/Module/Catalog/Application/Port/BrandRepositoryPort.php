<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Domain\Entity\Brand;
use Doctrine\DBAL\LockMode;

interface BrandRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Brand;

    /** @return list<Brand> */
    public function findAllForAdmin(): array;

    public function existsWithName(string $name, ?int $excludeId = null): bool;

    public function findOneByName(string $name): ?Brand;
}
