<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Outbox\Domain\Entity\OutboxEvent;

final readonly class Outbox
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    /** @param array<string, mixed> $payload */
    public function record(string $key, string $type, array $payload, ?\DateTimeImmutable $availableAt = null): OutboxEvent
    {
        $event = new OutboxEvent($key, $type, $payload, $availableAt);
        $this->persistence->persist($event);

        return $event;
    }
}
