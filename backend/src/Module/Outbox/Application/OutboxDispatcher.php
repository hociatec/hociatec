<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Infrastructure\Application\TransactionManager;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class OutboxDispatcher
{
    /** @param iterable<OutboxEventHandler> $handlers */
    public function __construct(
        private OutboxEventStore $events,
        private DoctrineUnitOfWork $persistence,
        private TransactionManager $transactions,
        #[AutowireIterator('app.outbox_handler')]
        private iterable $handlers,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatchDue(int $limit = 50): int
    {
        $processed = 0;
        foreach ($this->reserveDue($limit) as $event) {
            if ($this->dispatch($event)) {
                ++$processed;
            }
        }

        return $processed;
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
            $this->persistence->commit();
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
            $event->markFailed($this->failureMessage($exception), new \DateTimeImmutable(sprintf('+%d minutes', min(60, max(1, $event->getAttempts() * 5)))));
            $this->logger->error('Outbox event handling failed.', [
                'eventId' => $event->getId(),
                'type' => $event->getType(),
                'attempts' => $event->getAttempts(),
                'request_id' => $event->getRequestId(),
                'exception' => $exception,
            ]);
        }

        $this->persistence->commit();

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
