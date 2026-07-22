<?php

declare(strict_types=1);

namespace App\Module\Auth\Security;

use App\Module\Auth\Http\AuthCookieService;
use App\Module\Auth\Service\RefreshTokenService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
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
        ]);
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
