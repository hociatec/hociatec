<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\UI\Controller\LogoutController;
use App\Module\Auth\UI\Controller\RefreshTokenController;
use App\Module\Auth\UI\Controller\RequestPasswordResetController;
use App\Module\Auth\UI\Controller\ResetPasswordController;
use App\Module\Auth\UI\Controller\VerifyAccountController;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Auth\Infrastructure\Http\AuthCookieService;
use App\Module\Auth\Infrastructure\Repository\RefreshTokenRepository;
use App\Module\Auth\Infrastructure\Security\AuthenticationFailureHandler;
use App\Module\Auth\Infrastructure\Security\AuthenticationSuccessHandler;
use App\Module\Auth\Application\Service\PasswordResetService;
use App\Module\Auth\Application\Service\RefreshTokenPersistence;
use App\Module\Auth\Application\Service\RefreshTokenService;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Service\EmailTemplateRenderer;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\User\Application\Service\VerificationTokenHasher;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Outbox\Application\Outbox;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthModuleCompletionTest extends TestCase
{
    public function testRefreshTokenServiceRepositoryRefreshControllerAndLogout(): void
    {
        $em = $this->entityManager();
        $user = $this->user('refresh@example.com');
        $em->persist($user);
        $em->flush();
        $service = $this->refreshService($em);
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
        $jwt->expects(self::exactly(2))->method('create')->with($user)->willReturn('jwt-token');
        $controller = new RefreshTokenController($service, $jwt, new AuthCookieService('test'), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $controller(Request::create('/', 'POST', [], [], [], [], '{"refreshToken":""}'))->getStatusCode());
        $refreshed = $controller(Request::create('/', 'POST', [], [], [], [], json_encode(['refreshToken' => $issued['refreshToken']], JSON_THROW_ON_ERROR)));
        self::assertSame(Response::HTTP_OK, $refreshed->getStatusCode());
        $cookieIssued = $service->issueForUser($user);
        self::assertSame(Response::HTTP_OK, $controller(Request::create('/', 'POST', cookies: [AuthCookieService::REFRESH_COOKIE => $cookieIssued['refreshToken']], content: '{"refreshToken":"ignored"}'))->getStatusCode());

        $logout = new LogoutController(new AuthCookieService('test'), $service);
        $logoutResponse = $logout(Request::create('/', 'POST', cookies: [AuthCookieService::REFRESH_COOKIE => $issued['refreshToken']]));
        self::assertSame(Response::HTTP_OK, $logoutResponse->getStatusCode());
        $logout(Request::create('/', 'POST'));

        $throttled = new RefreshTokenController($service, $jwt, new AuthCookieService('test'), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(0));
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

    public function testPasswordResetControllersServiceAndVerifyController(): void
    {
        $em = $this->entityManager();
        $user = $this->user('reset@example.com');
        $em->persist($user);
        $em->flush();

        $passwords = $this->createMock(UserPasswordHasherInterface::class);
        $passwords->method('hashPassword')->willReturn('new-hash');
        $passwordReset = new PasswordResetService(
            $this->userRepository($em),
            new DoctrineUnitOfWork($em),
            new \App\Infrastructure\Persistence\DoctrineTransactionManager($em),
            $passwords,
            new Outbox(new DoctrineUnitOfWork($em)),
        );

        $requestController = new RequestPasswordResetController($passwordReset, $this->validator(1), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));
        self::assertSame(Response::HTTP_BAD_REQUEST, $requestController(Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $requestController(Request::create('/', 'POST', [], [], [], [], '{"email":"reset@example.com"}'))->getStatusCode());
        self::assertNotNull($user->getPasswordResetToken());
        $passwordReset->request('missing@example.com');

        $resetController = new ResetPasswordController($passwordReset, $this->validator(3), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController('bad', Request::create('/', 'POST', [], [], [], [], '{"password":"new"}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController(str_repeat('b', 64), Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController(str_repeat('d', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $resetController((string) $user->getPasswordResetToken(), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        self::assertSame('new-hash', $user->getPassword());

        $expiredReset = $this->user('expired-reset@example.com');
        $expiredReset->setPasswordResetToken(str_repeat('e', 64))->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        $em->persist($expiredReset);
        $em->flush();
        self::assertSame(Response::HTTP_BAD_REQUEST, $resetController(str_repeat('e', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());

        $throttledRequest = new RequestPasswordResetController($passwordReset, $this->validator(1), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(0));
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $throttledRequest(Request::create('/', 'POST', [], [], [], [], '{"email":"reset@example.com"}'))->getStatusCode());
        $throttledReset = new ResetPasswordController($passwordReset, $this->validator(2), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(1));
        self::assertSame(Response::HTTP_BAD_REQUEST, $throttledReset(str_repeat('f', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $throttledReset(str_repeat('f', 64), Request::create('/', 'POST', [], [], [], [], '{"password":"new-password"}'))->getStatusCode());

        $verifyUser = $this->user('verify@example.com');
        $rawToken = str_repeat('a', 64);
        $verifyUser->setVerificationToken(VerificationTokenHasher::hash($rawToken))->setVerificationTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $em->persist($verifyUser);
        $em->flush();

        $verify = new VerifyAccountController(new \App\Module\User\Application\Service\AccountVerificationService($this->userRepository($em), new DoctrineUnitOfWork($em)), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(10));
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
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, (new VerifyAccountController(new \App\Module\User\Application\Service\AccountVerificationService($this->userRepository($em), new DoctrineUnitOfWork($em)), new \App\Infrastructure\Http\RateLimitKeyFactory(), $this->limiter(0)))($rawToken, Request::create('/', server: ['REMOTE_ADDR' => '127.0.0.1']))->getStatusCode());
    }

    public function testAuthenticationHandlers(): void
    {
        $user = $this->user('login@example.com');
        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();

        $jwt = $this->createMock(JWTTokenManagerInterface::class);
        $jwt->method('create')->willReturn('jwt');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
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

    private function refreshService(EntityManager $em): RefreshTokenService
    {
        return new RefreshTokenService($this->refreshRepository($em), new RefreshTokenPersistence($em), new \App\Infrastructure\Persistence\DoctrineTransactionManager($em));
    }

    private function limiter(int $limit): RateLimiterFactory
    {
        $storage = new InMemoryStorage();
        $factory = new RateLimiterFactory(['id' => 'test_limiter', 'policy' => 'fixed_window', 'limit' => max(1, $limit), 'interval' => '1 minute'], $storage);
        if ($limit <= 0) {
            $keys = new \App\Infrastructure\Http\RateLimitKeyFactory();
            $request = Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
            $factory->create($keys->forRequest($request))->consume(1);
            $factory->create($keys->forRequest($request, 'reset@example.com'))->consume(1);
            $factory->create($keys->forRequest($request, str_repeat('f', 64)))->consume(1);
            $factory->create($keys->forRequest($request, str_repeat('a', 64)))->consume(1);
        }

        return $factory;
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string, scalar|null> $data */
    private function authException(string $message, array $data, int $code = 0): AuthenticationException
    {
        return new class($message, $data, $code) extends AuthenticationException {
            /** @param array<string, scalar|null> $data */
            public function __construct(private readonly string $key, private readonly array $data, int $code)
            {
                parent::__construct($key, $code);
            }

            public function getMessageKey(): string
            {
                return $this->key;
            }

            /** @return array<string, scalar|null> */
            public function getMessageData(): array
            {
                return $this->data;
            }
        };
    }

    private function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(RefreshToken::class),
            $em->getClassMetadata(OutboxEvent::class),
        ]);

        return $em;
    }

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function refreshRepository(EntityManager $em): RefreshTokenRepository
    {
        return new RefreshTokenRepository($this->registry($em));
    }

    private function userRepository(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }
}
