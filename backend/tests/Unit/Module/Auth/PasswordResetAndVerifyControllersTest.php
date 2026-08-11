<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Application\Workflow\PasswordResetService;
use App\Module\Auth\Application\Workflow\PasswordResetTokenHasher;
use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Auth\UI\Controller\RequestPasswordResetController;
use App\Module\Auth\UI\Controller\ResetPasswordController;
use App\Module\Auth\UI\Controller\VerifyAccountController;
use App\Module\Outbox\Application\Outbox;
use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Application\Workflow\VerificationTokenHasher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PasswordResetAndVerifyControllersTest extends AuthIntegrationTestCase
{
    public function testPasswordResetControllersServiceAndVerifyController(): void
    {
        $em = $this->entityManager();
        $user = $this->user('reset@example.com');
        $em->persist($user);
        $em->flush();

        $passwords = $this->createMock(UserPasswordHasher::class);
        $passwords->method('hashPassword')->willReturn('new-hash');
        $passwordReset = new PasswordResetService(
            $this->userRepository($em),
            new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em),
            new \App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager($em),
            $passwords,
            new Outbox(new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em)),
            new RefreshTokenRevocationService($this->refreshRepository($em)),
        );

        $requestController = new RequestPasswordResetController($passwordReset, $this->validator(1), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));
        self::assertSame(Response::HTTP_BAD_REQUEST, $requestController(Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $requestController(Request::create('/', 'POST', [], [], [], [], '{"email":"reset@example.com"}'))->getStatusCode());
        self::assertNotNull($user->getPasswordResetToken());
        self::assertSame(PasswordResetTokenHasher::HASH_ALGORITHM, 'sha256');
        self::assertSame(64, strlen((string) $user->getPasswordResetToken()));
        $passwordReset->request('missing@example.com');

        $refresh = $this->refreshService($em);
        $issued = $refresh->issueForUser($user);

        $resetController = new ResetPasswordController($passwordReset, $this->validator(4), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController('bad', Request::create('/', 'POST', [], [], [], [], '{"password":"new"}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController(str_repeat('b', 64), Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController(str_repeat('d', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        $event = $em->getRepository(\App\Module\Outbox\Domain\Entity\OutboxEvent::class)->findOneBy(['type' => 'auth.password_reset_email_requested'], ['id' => 'DESC']);
        self::assertInstanceOf(\App\Module\Outbox\Domain\Entity\OutboxEvent::class, $event);
        $payload = $event->getPayload();
        self::assertSame(Response::HTTP_OK, $resetController((string) ($payload['token'] ?? null), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        self::assertSame('new-hash', $user->getPassword());
        self::assertNull($refresh->rotate($issued['refreshToken']));

        $expiredReset = $this->user('expired-reset@example.com');
        $expiredReset->setPasswordResetToken(str_repeat('e', 64))->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        $em->persist($expiredReset);
        $em->flush();
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController(str_repeat('e', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());

        $legacyReset = $this->user('legacy-reset@example.com');
        $legacyToken = str_repeat('9', 64);
        $legacyReset->setPasswordResetToken($legacyToken)->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $em->persist($legacyReset);
        $em->flush();
        self::assertSame(Response::HTTP_OK, $resetController($legacyToken, Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());

        $throttledRequest = new RequestPasswordResetController($passwordReset, $this->validator(1), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(0));
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $throttledRequest(Request::create('/', 'POST', [], [], [], [], '{"email":"reset@example.com"}'))->getStatusCode());
        $throttledReset = new ResetPasswordController($passwordReset, $this->validator(3), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(1));
        self::assertSame(Response::HTTP_BAD_REQUEST, $throttledReset(str_repeat('f', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $throttledReset(str_repeat('f', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        self::assertStringNotContainsString('Unknown token', (string) $throttledReset(str_repeat('a', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getContent());

        $verifyUser = $this->user('verify@example.com');
        $rawToken = str_repeat('a', 64);
        $verifyUser->setVerificationToken(VerificationTokenHasher::hash($rawToken))->setVerificationTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $em->persist($verifyUser);
        $em->flush();

        $verify = new VerifyAccountController(new \App\Module\User\Application\Workflow\AccountVerificationService($this->userRepository($em), new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em)), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));
        self::assertSame(Response::HTTP_BAD_REQUEST, $verify('bad', Request::create('/'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $verify($rawToken, Request::create('/'))->getStatusCode());
        self::assertTrue($verifyUser->isVerified());
        self::assertSame(Response::HTTP_BAD_REQUEST, $verify(str_repeat('d', 64), Request::create('/'))->getStatusCode());

        $alreadyVerified = $this->user('already@example.com');
        $alreadyToken = str_repeat('c', 64);
        $alreadyVerified
            ->setVerificationToken(VerificationTokenHasher::hash($alreadyToken))
            ->setVerificationTokenExpiresAt(new \DateTimeImmutable('+1 hour'))
            ->setIsVerified(true);
        $em->persist($alreadyVerified);
        $em->flush();
        self::assertSame(Response::HTTP_OK, $verify($alreadyToken, Request::create('/'))->getStatusCode());

        $expired = $this->user('expired@example.com');
        $expiredToken = str_repeat('b', 64);
        $expired->setVerificationToken(VerificationTokenHasher::hash($expiredToken))->setVerificationTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        $em->persist($expired);
        $em->flush();
        self::assertSame(Response::HTTP_BAD_REQUEST, $verify($expiredToken, Request::create('/'))->getStatusCode());
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, (new VerifyAccountController(new \App\Module\User\Application\Workflow\AccountVerificationService($this->userRepository($em), new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em)), new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(0)))($rawToken, Request::create('/', server: ['REMOTE_ADDR' => '127.0.0.1']))->getStatusCode());
    }
}
