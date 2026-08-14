<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites/{productId}', name: 'api_favorites_remove', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class RemoveFavoriteController extends AbstractController
{
    public function __construct(
        private readonly ProductRepositoryPort $products,
        private readonly FavoriteService $favorites,
    ) {
    }

    public function __invoke(int $productId): JsonResponse
    {
        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $product = $this->products->find($productId);

        if (null !== $product) {
            $this->favorites->removeProduct($user, $product);
        }

        return ApiResponse::successItem('removed', true);
    }
}
