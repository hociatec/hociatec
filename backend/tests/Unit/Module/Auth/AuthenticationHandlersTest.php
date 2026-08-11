<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Infrastructure\Http\AuthCookieService;
use App\Module\Auth\Infrastructure\Security\AuthenticationFailureHandler;
use App\Module\Auth\Infrastructure\Security\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
        $jwt->method('create')->willReturn('jwt');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user));
        $success = new AuthenticationSuccessHandler($jwt, $this->refreshService($em), new AuthCookieService('prod'));
        self::assertSame(Response::HTTP_OK, $success->onAuthenticationSuccess(Request::create('/'), $token)->getStatusCode());

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::exactly(11))->method('dispatch')->willReturnArgument(0);
        $failure = new AuthenticationFailureHandler($dispatcher);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), new AuthenticationException('Bad credentials.'))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Invalid credentials.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Authentication credentials could not be found.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Account is disabled.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Account is locked.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Account has expired.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Credentials have expired.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Too many failed login attempts, please try again later.', []))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Too many failed login attempts, please try again in %minutes% minute.', ['%minutes%' => 1]))->getStatusCode());
        self::assertSame(Response::HTTP_UNAUTHORIZED, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Too many failed login attempts, please try again in %minutes% minutes.', ['%minutes%' => 3]))->getStatusCode());
        self::assertSame(Response::HTTP_FORBIDDEN, $failure->onAuthenticationFailure(Request::create('/'), $this->authException('Custom %name%', ['%name%' => 'error'], Response::HTTP_FORBIDDEN))->getStatusCode());
    }
}
