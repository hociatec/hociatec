<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Application\Port\RefreshTokenRepositoryPort;
use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Auth\Application\Workflow\RefreshTokenService;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use PHPUnit\Framework\TestCase;

final class RefreshTokenConcurrencyTest extends TestCase
{
    public function testConcurrentRotationAllowsOnlyOneWinnerWhenLockingIsSerialized(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('pcntl extension is required.');
        }

        $workspace = sys_get_temp_dir().'/refresh-token-concurrency-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($workspace, 0777, true));

        $lockPath = $workspace.'/refresh.lock';
        $statePath = $workspace.'/state.php';
        $signalPath = $workspace.'/child-locked.signal';
        $childResultPath = $workspace.'/child-result.php';

        try {
            $user = new User('concurrency@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
            $user->setPassword('hashed');

            $bootstrapRepository = new FileRefreshTokenRepository($statePath, $lockPath);
            $bootstrapUnitOfWork = new FileRefreshTokenUnitOfWork($bootstrapRepository);
            $bootstrapService = new RefreshTokenService(
                $bootstrapRepository,
                $bootstrapUnitOfWork,
                new FileTransactionManager($bootstrapRepository),
                new RefreshTokenRevocationService($bootstrapRepository),
            );
            $issued = $bootstrapService->issueForUser($user);

            $pid = pcntl_fork();
            self::assertNotSame(-1, $pid);

            if (0 === $pid) {
                try {
                    $repository = new FileRefreshTokenRepository($statePath, $lockPath, $signalPath, 700_000);
                    $service = new RefreshTokenService(
                        $repository,
                        new FileRefreshTokenUnitOfWork($repository),
                        new FileTransactionManager($repository),
                        new RefreshTokenRevocationService($repository),
                    );

                    $result = $service->rotate($issued['refreshToken']);
                    file_put_contents($childResultPath, serialize($result), LOCK_EX);
                    exit(0);
                } catch (\Throwable $exception) {
                    file_put_contents($childResultPath, serialize($exception), LOCK_EX);
                    exit(1);
                }
            }

            $deadline = microtime(true) + 5;
            while (!is_file($signalPath) && microtime(true) < $deadline) {
                usleep(10_000);
            }
            self::assertFileExists($signalPath);

            $parentRepository = new FileRefreshTokenRepository($statePath, $lockPath);
            $parentService = new RefreshTokenService(
                $parentRepository,
                new FileRefreshTokenUnitOfWork($parentRepository),
                new FileTransactionManager($parentRepository),
                new RefreshTokenRevocationService($parentRepository),
            );

            $startedAt = microtime(true);
            $parentResult = $parentService->rotate($issued['refreshToken']);
            $elapsed = microtime(true) - $startedAt;

            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertSame(0, pcntl_wexitstatus($status));
            self::assertFileExists($childResultPath);

            $childResult = unserialize((string) file_get_contents($childResultPath), ['allowed_classes' => true]);
            self::assertIsArray($childResult);
            self::assertArrayHasKey('refreshToken', $childResult);
            self::assertNull($parentResult);
            self::assertGreaterThanOrEqual(0.5, $elapsed);
        } finally {
            foreach ([$childResultPath, $signalPath, $statePath, $lockPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            if (is_dir($workspace)) {
                rmdir($workspace);
            }
        }
    }
}

final class FileRefreshTokenRepository implements RefreshTokenRepositoryPort
{
    /** @var resource|null */
    private $lockHandle = null;

    /** @var array<string, RefreshToken> */
    private array $tracked = [];

    public function __construct(
        private readonly string $statePath,
        private readonly string $lockPath,
        private readonly ?string $signalPath = null,
        private readonly int $sleepMicrosAfterLock = 0,
    ) {
    }

    public function findOneBySelector(string $selector): ?RefreshToken
    {
        return $this->load()[$selector] ?? null;
    }

    public function findOneBySelectorForUpdate(string $selector): ?RefreshToken
    {
        if (!is_resource($this->lockHandle)) {
            $this->lockHandle = fopen($this->lockPath, 'c+');
            if (false === $this->lockHandle) {
                throw new \RuntimeException('Unable to open refresh token lock file.');
            }
            flock($this->lockHandle, LOCK_EX);
            if (null !== $this->signalPath) {
                file_put_contents($this->signalPath, 'locked', LOCK_EX);
            }
            if ($this->sleepMicrosAfterLock > 0) {
                usleep($this->sleepMicrosAfterLock);
            }
        }

        $token = $this->load()[$selector] ?? null;
        if ($token instanceof RefreshToken) {
            $this->tracked[$selector] = $token;
        }

        return $token;
    }

    public function findActiveForUser(User $user): array
    {
        $tokens = array_filter(
            $this->load(),
            static fn (RefreshToken $token): bool => $token->getUser()->getEmail() === $user->getEmail() && !$token->isRevoked() && !$token->isExpired(),
        );

        usort($tokens, static function (RefreshToken $left, RefreshToken $right): int {
            $leftStamp = ($left->getLastUsedAt() ?? $left->getCreatedAt())->getTimestamp();
            $rightStamp = ($right->getLastUsedAt() ?? $right->getCreatedAt())->getTimestamp();

            return [$rightStamp, $right->getSelector()] <=> [$leftStamp, $left->getSelector()];
        });

        return array_values($tokens);
    }

    public function findOneActiveByIdForUser(int $id, User $user): ?RefreshToken
    {
        foreach ($this->load() as $token) {
            if ($token->getId() !== $id) {
                continue;
            }
            if ($token->getUser()->getEmail() !== $user->getEmail() || $token->isRevoked() || $token->isExpired()) {
                continue;
            }

            return $token;
        }

        return null;
    }

    public function findOneActiveBySelectorForUser(string $selector, User $user): ?RefreshToken
    {
        $token = $this->load()[$selector] ?? null;
        if (!$token instanceof RefreshToken) {
            return null;
        }

        if ($token->getUser()->getEmail() !== $user->getEmail() || $token->isRevoked() || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function revokeAllForUser(User $user): void
    {
        $tokens = $this->load();
        foreach ($tokens as $selector => $token) {
            if ($token->getUser()->getEmail() !== $user->getEmail() || $token->isRevoked()) {
                continue;
            }

            $token->revoke();
            $tokens[$selector] = $token;
        }

        $this->write($tokens);
    }

    public function revokeAllActive(): int
    {
        $tokens = $this->load();
        $revoked = 0;

        foreach ($tokens as $selector => $token) {
            if ($token->isRevoked() || $token->isExpired()) {
                continue;
            }

            $token->revoke();
            $tokens[$selector] = $token;
            ++$revoked;
        }

        $this->write($tokens);

        return $revoked;
    }

    public function revokeActiveTokensOverLimit(User $user, int $limit): int
    {
        $tokens = array_filter(
            $this->load(),
            static fn (RefreshToken $token): bool => $token->getUser()->getEmail() === $user->getEmail() && !$token->isRevoked() && !$token->isExpired(),
        );

        uasort($tokens, static function (RefreshToken $left, RefreshToken $right): int {
            return [$right->getCreatedAt()->getTimestamp(), $right->getSelector()] <=> [$left->getCreatedAt()->getTimestamp(), $left->getSelector()];
        });

        $revoked = 0;
        $offset = 0;
        foreach ($tokens as $selector => $token) {
            if ($offset++ < $limit) {
                continue;
            }

            $token->revoke();
            $this->tracked[$selector] = $token;
            ++$revoked;
        }

        return $revoked;
    }

    public function persist(RefreshToken $token): void
    {
        $this->tracked[$token->getSelector()] = $token;
    }

    public function flushTracked(): void
    {
        if ([] === $this->tracked) {
            return;
        }

        $tokens = $this->load();
        foreach ($this->tracked as $selector => $token) {
            $tokens[$selector] = $token;
        }

        $this->write($tokens);
    }

    public function releaseLock(): void
    {
        $this->flushTracked();
        $this->tracked = [];

        if (!is_resource($this->lockHandle)) {
            return;
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    /**
     * @return array<string, RefreshToken>
     */
    private function load(): array
    {
        if (!is_file($this->statePath)) {
            return [];
        }

        $payload = include $this->statePath;

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, RefreshToken> $tokens
     */
    private function write(array $tokens): void
    {
        file_put_contents($this->statePath, '<?php return unserialize('.var_export(serialize($tokens), true).');', LOCK_EX);
    }
}

final class FileRefreshTokenUnitOfWork implements UnitOfWork
{
    public function __construct(private readonly FileRefreshTokenRepository $repository)
    {
    }

    public function persist(object $entity): void
    {
        if (!$entity instanceof RefreshToken) {
            throw new \InvalidArgumentException('Unexpected entity type.');
        }

        $this->repository->persist($entity);
    }

    public function remove(object $entity): void
    {
        throw new \LogicException('Remove is not used in this test double.');
    }

    public function flush(): void
    {
        $this->repository->flushTracked();
    }
}

final class FileTransactionManager implements TransactionManager
{
    public function __construct(private readonly FileRefreshTokenRepository $repository)
    {
    }

    public function transactional(\Closure $operation): mixed
    {
        try {
            return $operation();
        } finally {
            $this->repository->releaseLock();
        }
    }
}
