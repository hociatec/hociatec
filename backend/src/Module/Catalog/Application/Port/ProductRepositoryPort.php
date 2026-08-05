<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Port;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Product;
use Doctrine\DBAL\LockMode;

interface ProductRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Product;

    public function findForUpdate(int $id): ?Product;

    public function countByBrand(Brand $brand): int;
}
