<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Favorite\UI\FavoriteViewFactory;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites/{category}/{targetId}', name: 'api_favorites_add_item', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class AddFavoriteItemController extends AbstractController
{
    public function __construct(
        private readonly FavoriteService $favorites,
        private readonly ProductRepositoryPort $products,
        private readonly ServiceOfferingRepositoryPort $services,
        private readonly NewsArticleRepositoryPort $articles,
        private readonly FavoriteViewFactory $views,
    ) {
    }

    public function __invoke(string $category, int $targetId): JsonResponse
    {
        if (!$this->targetExists($category, $targetId)) {
            return ApiResponse::error('Element introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        ['favorite' => $favorite, 'created' => $created] = $this->favorites->add($user, $category, $targetId);

        return ApiResponse::success([
            'favorite' => $this->views->favorite($favorite),
            'alreadyFavorite' => false === $created,
        ], $created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    private function targetExists(string $category, int $targetId): bool
    {
        return match (Favorite::normalizeCategory($category)) {
            Favorite::CATEGORY_PRODUCT => ($product = $this->products->find($targetId)) && $product->isPublished(),
            Favorite::CATEGORY_SERVICE => null !== $this->services->find($targetId),
            Favorite::CATEGORY_NEWS => ($article = $this->articles->find($targetId)) && $article->isPublished() && (null === $article->getPublishedAt() || $article->getPublishedAt() <= new \DateTimeImmutable()),
            Favorite::CATEGORY_PODCAST => false,
            default => false,
        };
    }
}
