<?php

declare(strict_types=1);

namespace App\Module\Favorite\Controller;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Favorite\Service\FavoriteService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites/{productId}', name: 'api_favorites_add', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class AddFavoriteController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly FavoriteService $favorites,
    ) {
    }

    public function __invoke(int $productId): JsonResponse
    {
        $product = $this->products->find($productId);

        if ($product === null || $product->isPublished() === false) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        ['favorite' => $favorite, 'created' => $created] = $this->favorites->addProduct($user, $product);

        $payload = [
            'favorite' => [
                'product' => CatalogFormatter::formatProduct($favorite->getProduct()),
                'addedAt' => $favorite->getCreatedAt()->format(DATE_ATOM),
            ],
            'alreadyFavorite' => $created === false,
        ];

        $status = $created ? Response::HTTP_CREATED : Response::HTTP_OK;

        return ApiResponse::success($payload, $status);
    }
}
