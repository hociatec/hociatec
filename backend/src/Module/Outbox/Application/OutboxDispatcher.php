<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class OutboxDispatcher
{
    private const MAX_ATTEMPTS = 5;

    /** @param iterable<OutboxEventHandler> $handlers */
    public function __construct(
        private OutboxEventStore $events,
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        #[AutowireIterator('app.outbox_handler')]
        private iterable $handlers,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatchDue(int $limit = 50, ?\DateTimeImmutable $staleProcessingThreshold = null): int
    {
        $this->recoverStaleProcessing($staleProcessingThreshold ?? new \DateTimeImmutable('-15 minutes'));
        $processed = 0;
        foreach ($this->reserveDue($limit) as $event) {
            if ($this->dispatch($event)) {
                ++$processed;
            }
        }

        return $processed;
    }

    public function recoverStaleProcessing(\DateTimeImmutable $threshold): int
    {
        $recovered = $this->events->recoverStaleProcessing($threshold);
        if ($recovered > 0) {
            $this->logger->warning('Recovered stale outbox processing events.', ['count' => $recovered]);
        }

        return $recovered;
    }

    /** @return list<OutboxEvent> */
    private function reserveDue(int $limit): array
    {
        return $this->transactions->transactional(function () use ($limit): array {
            $events = $this->events->findDueForUpdate($limit);

            foreach ($events as $event) {
                $event->retry()->markProcessing();
            }

            return $events;
        });
    }

    private function dispatch(OutboxEvent $event): bool
    {
        $handler = $this->handlerFor($event);
        if (null === $handler) {
            $event->markDead('No outbox handler registered for event type '.$event->getType().'.');
            $this->persistence->flush();
            $this->logger->warning('No outbox handler registered.', [
                'eventId' => $event->getId(),
                'type' => $event->getType(),
                'request_id' => $event->getRequestId(),
            ]);

            return false;
        }

        try {
            $handler->handle($event);
            $event->markProcessed();
        } catch (\Throwable $exception) {
            $failureMessage = $this->failureMessage($exception);
            if ($event->getAttempts() >= self::MAX_ATTEMPTS) {
                $event->markDead($failureMessage);
            } else {
                $event->markFailed($failureMessage, new \DateTimeImmutable(sprintf('+%d minutes', min(60, max(1, $event->getAttempts() * 5)))));
            }
            $this->logger->error('Outbox event handling failed.', [
                'eventId' => $event->getId(),
                'type' => $event->getType(),
                'attempts' => $event->getAttempts(),
                'request_id' => $event->getRequestId(),
                'exception' => $exception,
            ]);
        }

        $this->persistence->flush();

        return true;
    }

    private function failureMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        return '' !== $message ? $message : $exception::class;
    }

    private function handlerFor(OutboxEvent $event): ?OutboxEventHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($event)) {
                return $handler;
            }
        }

        return null;
    }
}
