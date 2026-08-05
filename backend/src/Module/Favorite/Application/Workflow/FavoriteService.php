<?php

declare(strict_types=1);

namespace App\Module\Favorite\Application\Workflow;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\Application\Port\FavoritePersistencePort;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Favorite\Application\Port\FavoriteRepositoryPort;
use App\Module\User\Domain\Entity\User;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryPort $favorites,
        private readonly FavoritePersistencePort $persistence,
    ) {
    }

    /**
     * @return list<Favorite>
     */
    public function listForUser(User $user): array
    {
        return $this->favorites->findFavoritesForUser($user);
    }

    /**
     * @return array{favorite: Favorite, created: bool}
     */
    public function addProduct(User $user, Product $product): array
    {
        $existing = $this->favorites->findOneByUserAndProduct($user, $product);
        if (null !== $existing) {
            return [
                'favorite' => $existing,
                'created' => false,
            ];
        }

        $favorite = new Favorite($user, $product);
        $this->persistence->save($favorite);
        $this->persistence->commit();

        return [
            'favorite' => $favorite,
            'created' => true,
        ];
    }

    public function removeProduct(User $user, Product $product): void
    {
        $favorite = $this->favorites->findOneByUserAndProduct($user, $product);
        if (null === $favorite) {
            return;
        }

        $this->persistence->delete($favorite);
        $this->persistence->commit();
    }

    public function isFavorite(User $user, Product $product): bool
    {
        return $this->favorites->existsForUserAndProduct($user, $product);
    }
}
