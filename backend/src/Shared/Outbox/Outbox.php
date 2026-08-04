<?php

declare(strict_types=1);

namespace App\Shared\Outbox;

use App\Shared\Outbox\Entity\OutboxEvent;
use App\Shared\Persistence\DoctrinePersistence;

final readonly class Outbox
{
    public function __construct(private DoctrinePersistence $persistence)
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
