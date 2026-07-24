<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Module\Auth\Http\AuthCookieService;
use App\Module\Auth\Service\RefreshTokenService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly AuthCookieService $authCookieService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get(AuthCookieService::REFRESH_COOKIE);
        if (is_string($refreshToken) && '' !== $refreshToken) {
            $this->refreshTokenService->revokePlainToken($refreshToken);
        }

        $response = ApiResponse::success(['message' => 'Déconnexion effectuée.']);
        $this->authCookieService->clearAuthCookies($response, $request);

        return $response;
    }
}
