<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Outbox;

use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Application\OutboxDispatcher;
use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Application\OutboxEventStore;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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

    public function testDispatcherMarksHandledEventsAsProcessed(): void
    {
        $event = new OutboxEvent('key-1', 'test.event', ['id' => 1]);
        $repository = $this->repository([$event]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('flush');

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

        $processed = (new OutboxDispatcher($repository, new DoctrineUnitOfWork($entityManager), [$handler], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(1, $processed);
        self::assertSame(1, $handler->calls);
        self::assertSame(OutboxEvent::STATUS_PROCESSED, $event->getStatus());
        self::assertSame(1, $event->getAttempts());
    }

    public function testDispatcherMarksFailuresForRetry(): void
    {
        $event = new OutboxEvent('key-2', 'test.event', ['id' => 2]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('flush');

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

        (new OutboxDispatcher($this->repository([$event]), new DoctrineUnitOfWork($entityManager), [$handler], $this->createMock(LoggerInterface::class)))->dispatchDue();

        self::assertSame(OutboxEvent::STATUS_FAILED, $event->getStatus());
        self::assertSame('temporary failure', $event->getLastError());
        self::assertSame(1, $event->getAttempts());
    }

    /** @param list<OutboxEvent> $events */
    private function repository(array $events): OutboxEventStore
    {
        $repository = new class($events) implements OutboxEventStore {
            /** @param list<OutboxEvent> $events */
            public function __construct(private readonly array $events)
            {
            }

            public function findDue(int $limit): array
            {
                return $this->events;
            }
        };

        return $repository;
    }
}
