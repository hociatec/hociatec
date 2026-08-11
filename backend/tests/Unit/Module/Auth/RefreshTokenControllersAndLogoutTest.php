<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Auth\Infrastructure\Http\AuthCookieService;
use App\Module\Auth\UI\Controller\LogoutController;
use App\Module\Auth\UI\Controller\RefreshTokenController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RefreshTokenControllersAndLogoutTest extends AuthIntegrationTestCase
{
    public function testRefreshTokenServiceRepositoryRefreshControllerAndLogout(): void
    {
        $em = $this->entityManager();
        $user = $this->user('refresh@example.com');
        $em->persist($user);
        $em->flush();
        $service = $this->refreshService($em);
        $serviceReflection = new \ReflectionClass($service);
        self::assertSame(30, $serviceReflection->getConstant('REFRESH_TOKEN_TTL_DAYS'));
        self::assertSame(10, $serviceReflection->getConstant('MAX_ACTIVE_SESSIONS_PER_USER'));
        $issued = $service->issueForUser($user);
        $selector = explode('.', $issued['refreshToken'], 2)[0];

        $repository = $this->refreshRepository($em);
        self::assertNotNull($repository->findOneBySelector($selector));

        $activeTokens = [$issued['refreshToken']];
        for ($index = 0; $index < 10; ++$index) {
            $activeTokens[] = $service->issueForUser($user)['refreshToken'];
        }
        self::assertNull($service->rotate($activeTokens[0]));
        self::assertNotNull($service->rotate($activeTokens[10]));

        self::assertNull($service->rotate('bad-token'));
        self::assertNull($service->rotate($selector.'.wrong'));
        self::assertTrue((bool) $repository->findOneBySelector($selector)?->isRevoked());
        self::assertNull($service->rotate($selector.'.wrong'));
        $service->revokePlainToken('bad-token');
        $service->revokePlainToken($selector.'.wrong');

        $issued = $service->issueForUser($user);
        $service->revokePlainToken($issued['refreshToken']);
        self::assertTrue((bool) $repository->findOneBySelector(explode('.', $issued['refreshToken'], 2)[0])?->isRevoked());
        $service->revokePlainToken($issued['refreshToken']);
        $issued = $service->issueForUser($user);
        $jwt = $this->createMock(JWTTokenManagerInterface::class);
        $jwt->expects(self::exactly(2))
            ->method('create')
            ->with(self::callback(static fn (object $securityUser): bool => $securityUser instanceof \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser && $securityUser->domainIdentity() === $user))
            ->willReturn('jwt-token');
        $controller = new RefreshTokenController($service, $jwt, new AuthCookieService('test'), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $controller(Request::create('/', 'POST', [], [], [], [], '{"refreshToken":""}'))->getStatusCode());
        $refreshed = $controller(Request::create('/', 'POST', [], [], [], [], json_encode(['refreshToken' => $issued['refreshToken']], JSON_THROW_ON_ERROR)));
        self::assertSame(Response::HTTP_OK, $refreshed->getStatusCode());
        $refreshPayload = json_decode((string) $refreshed->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($refreshPayload);
        $parallelSession = $service->issueForUser($user)['refreshToken'];
        $rotatedRefreshToken = null;
        foreach ($refreshed->headers->getCookies() as $cookie) {
            if (AuthCookieService::REFRESH_COOKIE === $cookie->getName()) {
                $rotatedRefreshToken = $cookie->getValue();
                break;
            }
        }
        self::assertNull($service->rotate($issued['refreshToken']));
        self::assertIsString($rotatedRefreshToken);
        self::assertNotSame('', $rotatedRefreshToken);
        self::assertNotNull($service->rotate($rotatedRefreshToken));
        self::assertNotNull($service->rotate($parallelSession));
        $cookieIssued = $service->issueForUser($user);
        self::assertSame(Response::HTTP_OK, $controller(Request::create('/', 'POST', cookies: [AuthCookieService::REFRESH_COOKIE => $cookieIssued['refreshToken']], content: '{"refreshToken":"ignored"}'))->getStatusCode());

        $logout = new LogoutController(new AuthCookieService('test'), $service);
        $logoutResponse = $logout(Request::create('/', 'POST', cookies: [AuthCookieService::REFRESH_COOKIE => $issued['refreshToken']]));
        self::assertSame(Response::HTTP_OK, $logoutResponse->getStatusCode());
        $logout(Request::create('/', 'POST'));

        $throttled = new RefreshTokenController($service, $jwt, new AuthCookieService('test'), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(0));
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $throttled(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']))->getStatusCode());

        $expired = new RefreshToken($user, 'expired', hash('sha256', 'secret'), new \DateTimeImmutable('-1 hour'));
        $repository->save($expired);
        $em->flush();
        self::assertNull($service->rotate('expired.secret'));

        $manual = new RefreshToken($user, 'manual', hash('sha256', 'secret'), new \DateTimeImmutable('+1 hour'));
        $repository->save($manual);
        $em->flush();
        $repository->revokeAllForUser($user);
        $em->flush();
        self::assertTrue($manual->isRevoked());
        $repository->remove($manual);
        $em->flush();
        self::assertNull($repository->findOneBySelector('manual'));
    }
}
