<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Outbox;

use App\Module\Outbox\Application\OutboxDispatcher;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Application\OutboxMetrics;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class OutboxConcurrencyTest extends TestCase
{
    public function testConcurrentDispatchAllowsOnlyOneWorkerToReserveSameEvent(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('pcntl extension is required.');
        }

        $workspace = sys_get_temp_dir().'/outbox-concurrency-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($workspace, 0777, true));

        $statePath = $workspace.'/outbox-state.php';
        $lockPath = $workspace.'/outbox.lock';
        $signalPath = $workspace.'/outbox.signal';
        $childResultPath = $workspace.'/child-result.php';

        try {
            file_put_contents($statePath, '<?php return '.var_export([
                [
                    'key' => 'evt-1',
                    'type' => 'test.event',
                    'status' => OutboxEvent::STATUS_PENDING,
                    'attempts' => 0,
                ],
            ], true).';', LOCK_EX);

            $pid = pcntl_fork();
            self::assertNotSame(-1, $pid);

            if (0 === $pid) {
                try {
                    $repository = new FileConcurrentOutboxStore($statePath, $lockPath, $signalPath, 700_000);
                    $dispatcher = new OutboxDispatcher(
                        $repository,
                        new FileConcurrentOutboxUnitOfWork($repository),
                        new FileConcurrentOutboxTransactionManager($repository),
                        [$this->handler()],
                        $this->createMock(LoggerInterface::class),
                    );

                    file_put_contents($childResultPath, serialize($dispatcher->dispatchDue()), LOCK_EX);
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

            $repository = new FileConcurrentOutboxStore($statePath, $lockPath);
            $dispatcher = new OutboxDispatcher(
                $repository,
                new FileConcurrentOutboxUnitOfWork($repository),
                new FileConcurrentOutboxTransactionManager($repository),
                [$this->handler()],
                $this->createMock(LoggerInterface::class),
            );

            $startedAt = microtime(true);
            $parentProcessed = $dispatcher->dispatchDue();
            $elapsed = microtime(true) - $startedAt;

            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertSame(0, pcntl_wexitstatus($status));
            self::assertFileExists($childResultPath);

            $childProcessed = unserialize((string) file_get_contents($childResultPath), ['allowed_classes' => true]);
            self::assertSame(1, $childProcessed);
            self::assertSame(0, $parentProcessed);
            self::assertGreaterThanOrEqual(0.5, $elapsed);

            $state = include $statePath;
            self::assertIsArray($state);
            self::assertSame(OutboxEvent::STATUS_PROCESSED, $state[0]['status'] ?? null);
            self::assertSame(1, $state[0]['attempts'] ?? null);
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

    private function handler(): OutboxEventHandler
    {
        return new class implements OutboxEventHandler {
            public function supports(OutboxEvent $event): bool
            {
                return 'test.event' === $event->getType();
            }

            public function handle(OutboxEvent $event): void
            {
            }
        };
    }
}

final class FileConcurrentOutboxStore implements OutboxEventStore
{
    /** @var resource|null */
    private $lockHandle = null;

    /** @var array<string, OutboxEvent> */
    private array $tracked = [];

    public function __construct(
        private readonly string $statePath,
        private readonly string $lockPath,
        private readonly ?string $signalPath = null,
        private readonly int $sleepMicrosAfterLock = 0,
    ) {
    }

    public function findDueForUpdate(int $limit): array
    {
        if (!is_resource($this->lockHandle)) {
            $this->lockHandle = fopen($this->lockPath, 'c+');
            if (false === $this->lockHandle) {
                throw new \RuntimeException('Unable to open outbox lock file.');
            }
            flock($this->lockHandle, LOCK_EX);
            if (null !== $this->signalPath) {
                file_put_contents($this->signalPath, 'locked', LOCK_EX);
            }
            if ($this->sleepMicrosAfterLock > 0) {
                usleep($this->sleepMicrosAfterLock);
            }
        }

        $events = [];
        foreach (array_slice($this->load(), 0, max(1, $limit)) as $row) {
            if (!in_array($row['status'] ?? null, [OutboxEvent::STATUS_PENDING, OutboxEvent::STATUS_FAILED], true)) {
                continue;
            }

            $event = new OutboxEvent((string) $row['key'], (string) $row['type'], ['id' => 1]);
            for ($i = 0; $i < (int) ($row['attempts'] ?? 0); ++$i) {
                $event->markProcessing();
            }
            $this->tracked[$event->getKey()] = $event;
            $events[] = $event;
        }

        return $events;
    }

    public function recoverStaleProcessing(\DateTimeImmutable $threshold): int
    {
        return 0;
    }

    public function purgeFinalizedBefore(\DateTimeImmutable $threshold): int
    {
        return 0;
    }

    public function metricsSnapshot(\DateTimeImmutable $staleProcessingThreshold): OutboxMetrics
    {
        return new OutboxMetrics(0, null, 0, 0, 0);
    }

    public function flushTracked(): void
    {
        if ([] === $this->tracked) {
            return;
        }

        $rows = $this->load();
        foreach ($rows as &$row) {
            $event = $this->tracked[$row['key'] ?? ''] ?? null;
            if (!$event instanceof OutboxEvent) {
                continue;
            }

            $row['status'] = $event->getStatus();
            $row['attempts'] = $event->getAttempts();
        }
        unset($row);

        file_put_contents($this->statePath, '<?php return '.var_export($rows, true).';', LOCK_EX);
    }

    public function releaseLock(): void
    {
        $this->flushTracked();

        if (!is_resource($this->lockHandle)) {
            return;
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    /**
     * @return list<array{key:string,type:string,status:string,attempts:int}>
     */
    private function load(): array
    {
        $payload = include $this->statePath;

        return is_array($payload) ? $payload : [];
    }
}

final class FileConcurrentOutboxUnitOfWork implements UnitOfWork
{
    public function __construct(private readonly FileConcurrentOutboxStore $repository)
    {
    }

    public function persist(object $entity): void
    {
    }

    public function remove(object $entity): void
    {
    }

    public function flush(): void
    {
        $this->repository->flushTracked();
    }
}

final class FileConcurrentOutboxTransactionManager implements TransactionManager
{
    public function __construct(private readonly FileConcurrentOutboxStore $repository)
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
