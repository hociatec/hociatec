<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\Application\Projection\RefreshTokenSessionFormatter;
use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\RefreshTokenRequestContextResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/sessions', name: 'api_auth_sessions_list', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListSessionsController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly RefreshTokenRevocationService $revocations,
        private readonly RefreshTokenSessionFormatter $formatter,
        private readonly RefreshTokenRequestContextResolver $contextResolver,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $currentSelector = $this->contextResolver->currentRefreshTokenSelector($request);
        $items = array_map(
            fn (\App\Module\Auth\Domain\Entity\RefreshToken $token): array => $this->formatter->format($token, $currentSelector),
            $this->revocations->activeSessionsForUser($this->currentUser()),
        );

        return ApiResponse::success([
            'items' => $items,
            'total' => count($items),
        ]);
    }
}
