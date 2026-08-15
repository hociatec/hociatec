<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Auth\Application\Workflow\RefreshTokenService;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Auth\Infrastructure\Http\RefreshTokenRequestContextResolver;
use App\Module\Auth\Infrastructure\Repository\RefreshTokenRepository;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AuthIntegrationTestCase extends TestCase
{
    protected function refreshService(EntityManager $em): RefreshTokenService
    {
        $repository = $this->refreshRepository($em);

        return new RefreshTokenService(
            $repository,
            new DoctrineUnitOfWork($em),
            new \App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager($em),
            new RefreshTokenRevocationService($repository, new DoctrineUnitOfWork($em)),
        );
    }

    protected function limiter(int $limit): RateLimiterFactory
    {
        $storage = new InMemoryStorage();
        $factory = new RateLimiterFactory(['id' => 'test_limiter', 'policy' => 'fixed_window', 'limit' => max(1, $limit), 'interval' => '1 minute'], $storage);
        if ($limit <= 0) {
            $keys = new \App\Shared\Infrastructure\Http\RateLimitKeyFactory();
            $request = Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
            $factory->create($keys->forRequest($request))->consume(1);
            $factory->create($keys->forRequest($request, 'reset@example.com'))->consume(1);
            $factory->create($keys->forRequest($request, str_repeat('f', 64)))->consume(1);
            $factory->create($keys->forRequest($request, str_repeat('a', 64)))->consume(1);
        }

        return $factory;
    }

    protected function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string, scalar|null> $data */
    protected function authException(string $message, array $data, int $code = 0): AuthenticationException
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

    protected function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    protected function entityManager(): EntityManager
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

    protected function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    protected function refreshRepository(EntityManager $em): RefreshTokenRepository
    {
        return new RefreshTokenRepository($this->registry($em));
    }

    protected function userRepository(EntityManager $em): UserRepository
    {
        return new UserRepository($this->registry($em));
    }
}
