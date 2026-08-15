<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Infrastructure\Http\AuthCookieService;
use App\Module\Auth\Infrastructure\Security\AuthenticationFailureHandler;
use App\Module\Auth\Infrastructure\Security\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Shared\Infrastructure\Http\RefreshTokenRequestContextResolver;
use App\Shared\Infrastructure\Http\SessionBoundJwtIssuer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class AuthenticationHandlersTest extends AuthIntegrationTestCase
{
    public function testAuthenticationHandlers(): void
    {
        $user = $this->user('login@example.com');
        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();

        $jwt = $this->createMock(JWTTokenManagerInterface::class);
        $jwt->expects(self::once())
            ->method('createFromPayload')
            ->with(
                self::callback(static fn (object $securityUser): bool => $securityUser instanceof \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser && $securityUser->domainIdentity() === $user),
                self::callback(static fn (array $payload): bool => is_string($payload[SessionBoundJwtIssuer::SESSION_SELECTOR_CLAIM] ?? null) && '' !== $payload[SessionBoundJwtIssuer::SESSION_SELECTOR_CLAIM]),
            )
            ->willReturn('jwt');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user));
        $success = new AuthenticationSuccessHandler(new SessionBoundJwtIssuer($jwt), $this->refreshService($em), new AuthCookieService('prod'), new RefreshTokenRequestContextResolver());
        self::assertSame(Response::HTTP_OK, $success->onAuthenticationSuccess(Request::create('/'), $token)->getStatusCode());

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::exactly(11))->method('dispatch')->willReturnArgument(0);
        $failure = new AuthenticationFailureHandler($dispatcher);

        foreach ([
            $this->authException('Bad credentials.', []),
            $this->authException('Invalid credentials.', []),
            $this->authException('Authentication credentials could not be found.', []),
            $this->authException('Account is disabled.', []),
            $this->authException('Account is locked.', []),
            $this->authException('Account has expired.', []),
            $this->authException('Credentials have expired.', []),
        ] as $exception) {
            $response = $failure->onAuthenticationFailure(Request::create('/'), $exception);
            self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
            $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('Identifiants invalides.', $payload['message']);
        }

        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Too many failed login attempts, please try again later.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Too many failed login attempts, please try again in %minutes% minute.', ['%minutes%' => 1]))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Too many failed login attempts, please try again in %minutes% minutes.', ['%minutes%' => 3]))->getStatusCode());
        self::assertSame(Response::HTTP_FORBIDDEN, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Custom %name%', ['%name%' => 'error'], Response::HTTP_FORBIDDEN))->getStatusCode());
    }
}
