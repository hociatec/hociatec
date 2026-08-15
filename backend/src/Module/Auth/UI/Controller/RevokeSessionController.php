<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Auth\Infrastructure\Http\RefreshTokenRequestContextResolver;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\AuthCookieResponseWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/sessions/{id}', name: 'api_auth_sessions_revoke_one', methods: ['DELETE'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_USER')]
final class RevokeSessionController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly RefreshTokenRevocationService $revocations,
        private readonly RefreshTokenRequestContextResolver $contextResolver,
        private readonly AuthCookieResponseWriter $authCookieService,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $token = $this->revocations->revokeOneForUser($this->currentUser(), $id);
        if (null === $token) {
            return ApiResponse::error('Accès introuvable.', 404);
        }

        $currentSelector = $this->contextResolver->currentRefreshTokenSelector($request);
        $revokedCurrentSession = $currentSelector === $token->getSelector();

        $response = ApiResponse::success([
            'message' => 'Accès révoqué.',
            'revokedCurrentSession' => $revokedCurrentSession,
        ]);

        if ($revokedCurrentSession) {
            $this->authCookieService->clearAuthCookies($response, $request);
        }

        return $response;
    }
}
