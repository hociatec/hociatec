<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\AuthCookieResponseWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/sessions/revoke-all', name: 'api_auth_sessions_revoke_all', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class RevokeAllSessionsController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly RefreshTokenRevocationService $revocations,
        private readonly AuthCookieResponseWriter $authCookieService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->revocations->revokeAllForUser($this->currentUser());

        $response = ApiResponse::success([
            'message' => 'Tous les acces ont ete revoques.',
        ]);
        $this->authCookieService->clearAuthCookies($response, $request);

        return $response;
    }
}
