<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Auth\Application\Service\RefreshTokenService;
use App\Module\Auth\Infrastructure\Http\AuthCookieService;
use App\Module\User\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly AuthCookieService $authCookieService,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var User $user */
        $user = $token->getUser();

        $jwt = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenService->issueForUser($user);

        $response = ApiResponse::success([
            'authenticated' => true,
            'refreshTokenExpiresAt' => $refreshToken['expiresAt'],
        ], 200, 'Connexion réussie.');
        $this->authCookieService->attachLoginCookies(
            $response,
            $request,
            $jwt,
            $refreshToken['refreshToken'],
            $refreshToken['expiresAt'],
        );

        return $response;
    }
}
