<?php

declare(strict_types=1);

namespace App\Module\Favorite\Application\Workflow;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\Application\Port\FavoriteRepositoryPort;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryPort $favorites,
        private readonly UnitOfWork $persistence,
    ) {
    }

    /**
     * @return list<Favorite>
     */
    public function listForUser(User $user, string|int|null $category = null, ?int $limit = null, ?int $offset = null): array
    {
        if (is_int($category)) {
            return $this->favorites->findFavoritesForUser($user, null, $category, $limit ?? 0);
        }

        return $this->favorites->findFavoritesForUser($user, $this->normalizeCategory($category), $limit ?? 20, $offset ?? 0);
    }

    public function countForUser(User $user, ?string $category = null): int
    {
        return $this->favorites->countFavoritesForUser($user, $this->normalizeCategory($category));
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
        $this->persistence->persist($favorite);
        $this->persistence->flush();

        return [
            'favorite' => $favorite,
            'created' => true,
        ];
    }

    /**
     * @return array{favorite: Favorite, created: bool}
     */
    public function add(User $user, string $category, int $targetId): array
    {
        $normalizedCategory = Favorite::normalizeCategory($category);
        $existing = $this->favorites->findOneByUserAndTarget($user, $normalizedCategory, $targetId);
        if (null !== $existing) {
            return [
                'favorite' => $existing,
                'created' => false,
            ];
        }

        $favorite = new Favorite($user, $normalizedCategory, $targetId);
        $this->persistence->persist($favorite);
        $this->persistence->flush();

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

        $this->persistence->remove($favorite);
        $this->persistence->flush();
    }

    public function delete(User $user, string $category, int $targetId): void
    {
        $favorite = $this->favorites->findOneByUserAndTarget($user, Favorite::normalizeCategory($category), $targetId);
        if (null === $favorite) {
            return;
        }

        $this->persistence->remove($favorite);
        $this->persistence->flush();
    }

    public function isFavorite(User $user, string|Product $category, ?int $targetId = null): bool
    {
        if ($category instanceof Product) {
            return $this->favorites->existsForUserAndProduct($user, $category);
        }

        if (null === $targetId) {
            return false;
        }

        return $this->favorites->existsForUserAndTarget($user, Favorite::normalizeCategory($category), $targetId);
    }

    private function normalizeCategory(?string $category): ?string
    {
        if (null === $category || '' === trim($category)) {
            return null;
        }

        return Favorite::normalizeCategory($category);
    }
}
