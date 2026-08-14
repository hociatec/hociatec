<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\UI\FavoriteViewFactory;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites', name: 'api_favorites_list', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListFavoritesController extends AbstractController
{
    public function __construct(
        private readonly FavoriteService $favorites,
        private readonly FavoriteViewFactory $views,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $category = RequestQueryMapper::choice($request, 'category', \App\Module\Favorite\Domain\Entity\Favorite::categories());
        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());

        $items = array_values(array_filter(array_map(
            $this->views->favorite(...),
            $this->favorites->listForUser($user, $category, $pagination->perPage, $pagination->offset()),
        )));

        return ApiResponse::paginated($items, $pagination->metadata($this->favorites->countForUser($user, $category)));
    }
}
