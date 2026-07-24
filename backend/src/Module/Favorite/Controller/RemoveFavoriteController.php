<?php

declare(strict_types=1);

namespace App\Module\Favorite\Controller;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Favorite\Service\FavoriteService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites/{productId}', name: 'api_favorites_remove', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class RemoveFavoriteController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly FavoriteService $favorites,
    ) {
    }

    public function __invoke(int $productId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $product = $this->products->find($productId);

        if (null !== $product) {
            $this->favorites->removeProduct($user, $product);
        }

        return ApiResponse::success(['removed' => true]);
    }
}
