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
    public function listForUser(User $user, ?string $category = null, int $limit = 20, int $offset = 0): array
    {
        return $this->favorites->findFavoritesForUser($user, $this->normalizeCategory($category), $limit, $offset);
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
        return $this->add($user, Favorite::CATEGORY_PRODUCT, $product->getId() ?? 0);
    }

    /**
     * @return array{favorite: Favorite, created: bool}
     */
    public function add(User $user, string $category, int $targetId): array
    {
        $normalizedCategory = $this->normalizeCategory($category);
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
        $this->remove($user, Favorite::CATEGORY_PRODUCT, $product->getId() ?? 0);
    }

    public function remove(User $user, string $category, int $targetId): void
    {
        $favorite = $this->favorites->findOneByUserAndTarget($user, $this->normalizeCategory($category), $targetId);
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

        return $this->favorites->existsForUserAndTarget($user, $this->normalizeCategory($category), $targetId);
    }

    private function normalizeCategory(?string $category): ?string
    {
        if (null === $category || '' === trim($category)) {
            return null;
        }

        return Favorite::normalizeCategory($category);
    }
}
