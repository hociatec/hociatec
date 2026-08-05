<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\Domain\Entity\Favorite;
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
    public function __construct(private readonly FavoriteService $favorites)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 12, 50);
        /** @var User $user */
        $user = $this->getUser();

        $items = array_map(
            static fn (Favorite $favorite) => [
                'addedAt' => $favorite->getCreatedAt()->format(DATE_ATOM),
                'product' => CatalogFormatter::formatProduct($favorite->getProduct()),
            ],
            $this->favorites->listForUser($user, $pagination->perPage, $pagination->offset()),
        );

        return ApiResponse::paginated($items, $pagination->metadata($this->favorites->countForUser($user)));
    }
}
