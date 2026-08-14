<?php

declare(strict_types=1);

namespace App\Module\Favorite\UI\Controller;

use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites/{category}/{targetId}', name: 'api_favorites_remove_item', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
final class RemoveFavoriteItemController extends AbstractController
{
    public function __construct(
        private readonly FavoriteService $favorites,
    ) {
    }

    public function __invoke(string $category, int $targetId): JsonResponse
    {
        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $this->favorites->delete($user, $category, $targetId);

        return ApiResponse::successItem('removed', true);
    }
}
