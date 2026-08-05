<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\Application\Port\AuthCookiePort;
use App\Module\Auth\Application\Workflow\RefreshTokenService;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly AuthCookiePort $authCookieService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get(AuthCookiePort::REFRESH_COOKIE);
        if (is_string($refreshToken) && '' !== $refreshToken) {
            $this->refreshTokenService->revokePlainToken($refreshToken);
        }

        $response = ApiResponse::successItem('message', 'Déconnexion effectuée.');
        $this->authCookieService->clearAuthCookies($response, $request);

        return $response;
    }
}
