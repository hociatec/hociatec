<?php

declare(strict_types=1);

namespace App\Module\Favorite\Application\Port;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\User\Domain\Entity\User;

interface FavoriteRepositoryPort
{
    public function findOneByUserAndProduct(User $user, Product $product): ?Favorite;

    public function findOneByUserAndTarget(User $user, string $category, int $targetId): ?Favorite;

    public function existsForUserAndProduct(User $user, Product $product): bool;

    public function existsForUserAndTarget(User $user, string $category, int $targetId): bool;

    /** @return list<Favorite> */
    public function findFavoritesForUser(User $user, ?string $category = null, int $limit = 20, int $offset = 0): array;

    public function countFavoritesForUser(User $user, ?string $category = null): int;
}
