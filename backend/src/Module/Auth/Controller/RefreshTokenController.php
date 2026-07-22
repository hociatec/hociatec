<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Module\Auth\Http\AuthCookieService;
use App\Module\Auth\Service\RefreshTokenService;
use App\Shared\Http\ApiResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
class RefreshTokenController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly AuthCookieService $authCookieService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $refreshToken = $request->cookies->get(AuthCookieService::REFRESH_COOKIE);
        if (!is_string($refreshToken) || $refreshToken === '') {
            $refreshToken = is_array($payload) ? (string) ($payload['refreshToken'] ?? '') : '';
        }

        if ($refreshToken === '') {
            return ApiResponse::error('Refresh token manquant.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rotated = $this->refreshTokenService->rotate($refreshToken);
        if ($rotated === null) {
            return ApiResponse::error('Refresh token invalide ou expiré.', Response::HTTP_UNAUTHORIZED);
        }

        $jwt = $this->jwtManager->create($rotated['user']);

        $response = ApiResponse::success([
            'authenticated' => true,
            'refreshTokenExpiresAt' => $rotated['expiresAt'],
        ]);
        $this->authCookieService->attachLoginCookies(
            $response,
            $request,
            $jwt,
            $rotated['refreshToken'],
            $rotated['expiresAt'],
        );

        return $response;
    }
}
