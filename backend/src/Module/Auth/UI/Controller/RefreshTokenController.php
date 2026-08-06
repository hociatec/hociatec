<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\Application\Workflow\RefreshTokenService;
use App\Module\Auth\UI\Http\AuthCookieResponseWriter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\CsrfExempt;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
#[CsrfExempt]
class RefreshTokenController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly AuthCookieResponseWriter $authCookieService,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.auth_refresh')]
        private readonly RateLimiterFactory $refreshLimiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $refreshToken = $request->cookies->get(AuthCookieResponseWriter::REFRESH_COOKIE);
        if (!is_string($refreshToken) || '' === $refreshToken) {
            $refreshToken = (string) ($payload['refreshToken'] ?? '');
        }

        $limit = $this->refreshLimiter
            ->create($this->rateLimitKeys->forRequest($request, $this->refreshTokenSelector($refreshToken)))
            ->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de requêtes de renouvellement. Veuillez réessayer plus tard.',
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        if ('' === $refreshToken) {
            return ApiResponse::error('Refresh token manquant.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rotated = $this->refreshTokenService->rotate($refreshToken);
        if (null === $rotated) {
            return ApiResponse::error('Refresh token invalide ou expiré.', Response::HTTP_UNAUTHORIZED);
        }

        $jwt = $this->jwtManager->create(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($rotated['user']));

        $response = ApiResponse::success([
            'authenticated' => true,
            'refreshTokenExpiresAt' => $rotated['expiresAt'],
        ], 200, 'Session renouvelée.');
        $this->authCookieService->attachLoginCookies(
            $response,
            $request,
            $jwt,
            $rotated['refreshToken'],
            $rotated['expiresAt'],
        );

        return $response;
    }

    private function refreshTokenSelector(string $refreshToken): ?string
    {
        $selector = explode('.', $refreshToken, 2)[0];

        return '' !== $selector ? $selector : null;
    }
}
