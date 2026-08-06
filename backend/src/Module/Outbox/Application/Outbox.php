<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Module\Outbox\Application\Port\OutboxRequestContextPort;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Application\UnitOfWork;

final readonly class Outbox
{
    public function __construct(
        private UnitOfWork $persistence,
        private ?OutboxRequestContextPort $requestContext = null,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function record(string $key, string $type, array $payload, ?\DateTimeImmutable $availableAt = null): OutboxEvent
    {
        $event = new OutboxEvent($key, $type, $this->withRequestId($payload), $availableAt);
        $this->persistence->persist($event);

        return $event;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function withRequestId(array $payload): array
    {
        $requestId = $this->requestContext?->requestId();
        if (null === $requestId) {
            return $payload;
        }

        $metadata = \is_array($payload['_meta'] ?? null) ? $payload['_meta'] : [];
        $metadata['requestId'] = $requestId;
        $payload['_meta'] = $metadata;

        return $payload;
    }
}
