<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\UI\FavoriteViewFactory;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
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
        private readonly ProductRepositoryPort $products,
        private readonly FavoriteService $favorites,
        private readonly CatalogFormatter $catalogFormatter,
        private readonly ?FavoriteViewFactory $views = null,
    ) {
    }

    public function __invoke(int $productId): JsonResponse
    {
        $product = $this->products->find($productId);

        if (null === $product || false === $product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());

        ['favorite' => $favorite, 'created' => $created] = $this->favorites->addProduct($user, $product);

        $payload = [
            'favorite' => $this->formatFavorite($favorite),
            'alreadyFavorite' => false === $created,
        ];

        $status = $created ? Response::HTTP_CREATED : Response::HTTP_OK;

        return ApiResponse::success($payload, $status);
    }

    /** @return array<string, mixed>|null */
    private function formatFavorite(\App\Module\Favorite\Domain\Entity\Favorite $favorite): ?array
    {
        if (null !== $this->views) {
            return $this->views->favorite($favorite);
        }

        $product = $favorite->getProduct();
        if (null === $product) {
            return null;
        }

        return [
            'category' => \App\Module\Favorite\Domain\Entity\Favorite::CATEGORY_PRODUCT,
            'targetId' => $product->getId() ?? $favorite->getTargetId(),
            'addedAt' => $favorite->getCreatedAt()->format(DATE_ATOM),
            'product' => $this->catalogFormatter->formatProduct($product),
        ];
    }
}
