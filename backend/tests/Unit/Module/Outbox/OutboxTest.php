<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Outbox;

use App\Infrastructure\Http\RequestIdSubscriber;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Application\OutboxAlert;
use App\Module\Outbox\Application\OutboxAlertNotifier;
use App\Module\Outbox\Application\OutboxAlertPolicy;
use App\Module\Outbox\Application\OutboxDispatcher;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Application\OutboxMetrics;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Outbox\Infrastructure\Alert\WebhookOutboxAlertNotifier;
use App\Module\Outbox\Infrastructure\Command\DispatchOutboxCommand;
use App\Shared\Application\TransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OutboxTest extends TestCase
{
    public function testOutboxRecordsEventWithoutFlushing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(OutboxEvent::class));

        $event = (new Outbox(new DoctrineUnitOfWork($entityManager)))->record('order-paid-1', 'order.paid', ['orderId' => 1]);

        self::assertSame('order-paid-1', $event->getKey());
        self::assertSame('order.paid', $event->getType());
        self::assertSame(['orderId' => 1], $event->getPayload());
    }

    public function testOutboxCorrelatesEventsWithCurrentRequestId(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $request = Request::create('/');
        $request->attributes->set(RequestIdSubscriber::ATTRIBUTE, 'req-123');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = (new Outbox(new DoctrineUnitOfWork($entityManager), $requestStack))->record('key-request', 'test.event', ['id' => 1]);

        self::assertSame('req-123', $event->getRequestId());
        self::assertSame('req-123', $event->getPayload()['_meta']['requestId']);
    }

    public function testDispatcherMarksHandledEventsAsProcessed(): void
    {
        $event = new OutboxEvent('key-1', 'test.event', ['id' => 1]);
        $repository = $this->repository([$event]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $handler = new class implements OutboxEventHandler {
            public int $calls = 0;

            public function supports(OutboxEvent $event): bool
            {
                return 'test.event' === $event->getType();
            }

            public function handle(OutboxEvent $event): void
            {
                ++$this->calls;
            }
        };

        $processed = (new OutboxDispatcher($repository, new DoctrineUnitOfWork($entityManager), $this->transactions(), [$handler], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(1, $processed);
        self::assertSame(1, $handler->calls);
        self::assertSame(OutboxEvent::STATUS_PROCESSED, $event->getStatus());
        self::assertSame(1, $event->getAttempts());
    }

    public function testDispatcherMarksFailuresForRetry(): void
    {
        $event = new OutboxEvent('key-2', 'test.event', ['id' => 2]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $handler = new class implements OutboxEventHandler {
            public function supports(OutboxEvent $event): bool
            {
                return true;
            }

            public function handle(OutboxEvent $event): void
            {
                throw new \RuntimeException('temporary failure');
            }
        };

        (new OutboxDispatcher($this->repository([$event]), new DoctrineUnitOfWork($entityManager), $this->transactions(), [$handler], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(OutboxEvent::STATUS_FAILED, $event->getStatus());
        self::assertSame('temporary failure', $event->getLastError());
        self::assertSame(1, $event->getAttempts());
    }

    public function testDispatcherDeadLettersEventsWithoutHandler(): void
    {
        $event = new OutboxEvent('key-3', 'unknown.event', ['id' => 3]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $processed = (new OutboxDispatcher($this->repository([$event]), new DoctrineUnitOfWork($entityManager), $this->transactions(), [], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(0, $processed);
        self::assertSame(OutboxEvent::STATUS_DEAD, $event->getStatus());
        self::assertStringContainsString('No outbox handler', (string) $event->getLastError());
    }

    public function testDispatcherCatchesThrowableFailures(): void
    {
        $event = new OutboxEvent('key-4', 'test.event', ['id' => 4]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $handler = new class implements OutboxEventHandler {
            public function supports(OutboxEvent $event): bool
            {
                return true;
            }

            public function handle(OutboxEvent $event): void
            {
                throw new \TypeError('bad payload');
            }
        };

        $processed = (new OutboxDispatcher($this->repository([$event]), new DoctrineUnitOfWork($entityManager), $this->transactions(), [$handler], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(1, $processed);
        self::assertSame(OutboxEvent::STATUS_FAILED, $event->getStatus());
        self::assertSame('bad payload', $event->getLastError());
    }

    public function testDispatcherRecoversStaleProcessingEventsBeforeDispatch(): void
    {
        $repository = new class implements OutboxEventStore {
            public int $recoveries = 0;

            public function findDueForUpdate(int $limit): array
            {
                return [];
            }

            public function recoverStaleProcessing(\DateTimeImmutable $threshold): int
            {
                ++$this->recoveries;

                return 2;
            }

            public function purgeFinalizedBefore(\DateTimeImmutable $threshold): int
            {
                return 0;
            }

            public function metricsSnapshot(\DateTimeImmutable $staleProcessingThreshold): OutboxMetrics
            {
                return new OutboxMetrics(0, null, 0, 0, 0);
            }
        };

        $processed = (new OutboxDispatcher($repository, new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)), $this->transactions(), [], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(0, $processed);
        self::assertSame(1, $repository->recoveries);
    }

    public function testDispatchCommandEmitsOutboxAlertForCriticalMetrics(): void
    {
        $alerts = new class implements OutboxAlertNotifier {
            public ?OutboxAlert $alert = null;

            public function notify(OutboxAlert $alert): void
            {
                $this->alert = $alert;
            }
        };

        $repository = new class implements OutboxEventStore {
            public function findDueForUpdate(int $limit): array
            {
                return [];
            }

            public function recoverStaleProcessing(\DateTimeImmutable $threshold): int
            {
                return 0;
            }

            public function purgeFinalizedBefore(\DateTimeImmutable $threshold): int
            {
                return 1;
            }

            public function metricsSnapshot(\DateTimeImmutable $staleProcessingThreshold): OutboxMetrics
            {
                return new OutboxMetrics(2, 120, 1, 1, 0);
            }
        };

        $dispatcher = new OutboxDispatcher($repository, new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)), $this->transactions(), [], $this->createMock(LoggerInterface::class));
        $tester = new CommandTester(new DispatchOutboxCommand($dispatcher, $repository, $alerts));

        self::assertSame(0, $tester->execute([]));
        self::assertInstanceOf(OutboxAlert::class, $alerts->alert);
        self::assertSame('critical', $alerts->alert->severity);
        self::assertStringContainsString('Outbox alert emitted', $tester->getDisplay());
    }

    public function testOutboxAlertPolicyAndWebhookNotifier(): void
    {
        $policy = new OutboxAlertPolicy();
        self::assertNull($policy->alertFor(new OutboxMetrics(0, null, 0, 0, 0)));
        self::assertSame('warning', $policy->alertFor(new OutboxMetrics(1, 60, 1, 0, 0))?->severity);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(202);
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::once())
            ->method('request')
            ->with('POST', 'https://alerts.example/outbox', self::callback(static fn (array $options): bool => 'critical' === ($options['json']['severity'] ?? null)))
            ->willReturn($response);

        $notifier = new WebhookOutboxAlertNotifier($client, $this->createMock(LoggerInterface::class), 'https://alerts.example/outbox');
        $notifier->notify(new OutboxAlert('critical', 'Outbox blocked.', new OutboxMetrics(1, 120, 0, 1, 0)));
    }

    /** @param list<OutboxEvent> $events */
    private function repository(array $events): OutboxEventStore
    {
        $repository = new class($events) implements OutboxEventStore {
            /** @param list<OutboxEvent> $events */
            public function __construct(private readonly array $events)
            {
            }

            public function findDueForUpdate(int $limit): array
            {
                return $this->events;
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
        };

        return $repository;
    }

    private function transactions(): TransactionManager
    {
        return new class implements TransactionManager {
            public function transactional(\Closure $operation): mixed
            {
                return $operation();
            }
        };
    }
}
