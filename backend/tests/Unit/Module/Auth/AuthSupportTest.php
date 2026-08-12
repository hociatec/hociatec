<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Auth\Infrastructure\Command\PurgeE2eDataCommand;
use App\Module\Auth\Infrastructure\Command\SeedE2eDataCommand;
use App\Module\Auth\Infrastructure\Seed\E2eDataPurger;
use App\Module\Auth\Infrastructure\Http\AuthCookieService;
use App\Module\Auth\Infrastructure\Seed\E2eDataSeeder;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthSupportTest extends TestCase
{
    public function testRefreshTokenLifecycle(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $activeToken = new RefreshToken($user, 'selector', 'hash', new \DateTimeImmutable('+2 hours'));
        $expiredToken = new RefreshToken($user, 'selector-2', 'hash-2', new \DateTimeImmutable('-2 hours'));

        self::assertNull($activeToken->getId());
        self::assertSame($user, $activeToken->getUser());
        self::assertSame('selector', $activeToken->getSelector());
        self::assertSame('hash', $activeToken->getTokenHash());
        self::assertSame($activeToken->getExpiresAt()->format(DATE_ATOM), $activeToken->getExpiresAt()->format(DATE_ATOM));
        self::assertInstanceOf(\DateTimeImmutable::class, $activeToken->getCreatedAt());
        self::assertNull($activeToken->getRevokedAt());
        self::assertFalse($activeToken->isExpired());
        self::assertFalse($activeToken->isRevoked());
        self::assertTrue($expiredToken->isExpired());

        $activeToken->revoke();
        self::assertInstanceOf(\DateTimeImmutable::class, $activeToken->getRevokedAt());
        self::assertTrue($activeToken->isRevoked());
    }

    public function testAuthCookieServiceAttachesAndClearsCookies(): void
    {
        $service = new AuthCookieService('dev');
        $request = Request::create('https://example.com/api/auth/login');
        $response = new Response();

        $service->attachLoginCookies(
            $response,
            $request,
            'jwt-token',
            'refresh-token',
            '2026-08-10T09:00:00+00:00',
        );

        $cookies = $response->headers->getCookies();
        self::assertCount(2, $cookies);
        self::assertSame(AuthCookieService::ACCESS_COOKIE, $cookies[0]->getName());
        self::assertSame('jwt-token', $cookies[0]->getValue());
        self::assertSame('/api', $cookies[0]->getPath());
        self::assertTrue($cookies[0]->isSecure());
        self::assertTrue($cookies[0]->isHttpOnly());
        self::assertSame(AuthCookieService::REFRESH_COOKIE, $cookies[1]->getName());
        self::assertSame('refresh-token', $cookies[1]->getValue());
        self::assertSame('/api/auth', $cookies[1]->getPath());
        self::assertTrue($cookies[1]->isSecure());
        self::assertTrue($cookies[1]->isHttpOnly());

        $clearResponse = new Response();
        $service->clearAuthCookies($clearResponse, $request);
        $clearedCookies = $clearResponse->headers->getCookies();

        self::assertCount(2, $clearedCookies);
        self::assertSame(AuthCookieService::ACCESS_COOKIE, $clearedCookies[0]->getName());
        self::assertNull($clearedCookies[0]->getValue());
        self::assertSame(AuthCookieService::REFRESH_COOKIE, $clearedCookies[1]->getName());
        self::assertNull($clearedCookies[1]->getValue());
    }

    public function testAuthCookieServiceUsesProdEnvironmentForSecureCookies(): void
    {
        $service = new AuthCookieService('prod');
        $request = Request::create('http://example.com/api/auth/login');
        $response = new Response();

        $service->attachLoginCookies(
            $response,
            $request,
            'jwt-token',
            'refresh-token',
            '2026-08-10T09:00:00+00:00',
        );

        foreach ($response->headers->getCookies() as $cookie) {
            self::assertTrue($cookie->isSecure());
        }
    }

    public function testSeedE2eDataCommandDelegatesToSeeder(): void
    {
        $seeder = $this->createMock(E2eDataSeeder::class);
        $seeder->expects(self::once())->method('seed');

        $tester = new CommandTester(new SeedE2eDataCommand($seeder));
        $previousFlag = $_SERVER['APP_E2E'] ?? null;
        $_SERVER['APP_E2E'] = '1';

        try {
            self::assertSame(Command::SUCCESS, $tester->execute([]));
            self::assertStringContainsString('E2E users and orders seeded.', $tester->getDisplay());
        } finally {
            if (null === $previousFlag) {
                unset($_SERVER['APP_E2E']);
            } else {
                $_SERVER['APP_E2E'] = $previousFlag;
            }
        }
    }

    public function testSeedE2eDataCommandIsBlockedWithoutExplicitE2eFlag(): void
    {
        $seeder = $this->createMock(E2eDataSeeder::class);
        $seeder->expects(self::never())->method('seed');

        $tester = new CommandTester(new SeedE2eDataCommand($seeder));
        $previousFlag = $_SERVER['APP_E2E'] ?? null;
        unset($_SERVER['APP_E2E']);

        try {
            self::assertSame(Command::FAILURE, $tester->execute([]));
            self::assertStringContainsString('APP_E2E=1', $tester->getDisplay());
        } finally {
            if (null === $previousFlag) {
                unset($_SERVER['APP_E2E']);
            } else {
                $_SERVER['APP_E2E'] = $previousFlag;
            }
        }
    }

    public function testPurgeE2eDataCommandDelegatesToPurger(): void
    {
        $purger = $this->createMock(E2eDataPurger::class);
        $purger->expects(self::once())->method('purge')->willReturn(7);

        $tester = new CommandTester(new PurgeE2eDataCommand($purger));
        $previousFlag = $_SERVER['APP_E2E'] ?? null;
        $_SERVER['APP_E2E'] = '1';

        try {
            self::assertSame(Command::SUCCESS, $tester->execute([]));
            self::assertStringContainsString('E2E data purged (7 deleted rows).', $tester->getDisplay());
        } finally {
            if (null === $previousFlag) {
                unset($_SERVER['APP_E2E']);
            } else {
                $_SERVER['APP_E2E'] = $previousFlag;
            }
        }
    }
}
