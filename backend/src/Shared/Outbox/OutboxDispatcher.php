<?php

declare(strict_types=1);

namespace App\Shared\Outbox;

use App\Shared\Outbox\Entity\OutboxEvent;
use App\Shared\Persistence\DoctrinePersistence;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class OutboxDispatcher
{
    /** @param iterable<OutboxEventHandler> $handlers */
    public function __construct(
        private OutboxEventStore $events,
        private DoctrinePersistence $persistence,
        #[AutowireIterator('app.outbox_handler')]
        private iterable $handlers,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatchDue(int $limit = 50): int
    {
        $processed = 0;
        foreach ($this->events->findDue($limit) as $event) {
            $this->dispatch($event);
            ++$processed;
        }

        return $processed;
    }

    private function dispatch(OutboxEvent $event): void
    {
        $handler = $this->handlerFor($event);
        if (null === $handler) {
            $this->logger->warning('No outbox handler registered.', ['eventId' => $event->getId(), 'type' => $event->getType()]);

            return;
        }

        $event->retry()->markProcessing();
        $this->persistence->flush();

        try {
            $handler->handle($event);
            $event->markProcessed();
        } catch (\RuntimeException $exception) {
            $event->markFailed($exception->getMessage(), new \DateTimeImmutable(sprintf('+%d minutes', min(60, max(1, $event->getAttempts() * 5)))));
            $this->logger->error('Outbox event handling failed.', [
                'eventId' => $event->getId(),
                'type' => $event->getType(),
                'exception' => $exception,
            ]);
        }

        $this->persistence->flush();
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
