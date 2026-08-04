<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Favorite\Application\Service\FavoriteService;
use App\Module\User\Domain\Entity\User;
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

        if (null === $product || false === $product->isPublished()) {
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
            'alreadyFavorite' => false === $created,
        ];

        $status = $created ? Response::HTTP_CREATED : Response::HTTP_OK;

        return ApiResponse::success($payload, $status);
    }
}
