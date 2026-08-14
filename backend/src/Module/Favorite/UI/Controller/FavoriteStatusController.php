<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites/{category}/{targetId}/status', name: 'api_favorites_status', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class FavoriteStatusController extends AbstractController
{
    public function __construct(
        private readonly FavoriteService $favorites,
    ) {
    }

    public function __invoke(string $category, int $targetId): JsonResponse
    {
        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());

        return ApiResponse::success([
            'category' => Favorite::normalizeCategory($category),
            'targetId' => $targetId,
            'isFavorite' => $this->favorites->isFavorite($user, $category, $targetId),
        ]);
    }
}
