<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyAsyncMessageDispatcher implements AsyncMessageDispatcher
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function dispatch(object $message): void
    {
        $this->bus->dispatch($message);
    }
}
