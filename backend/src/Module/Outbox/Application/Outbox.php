<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Infrastructure\Http\RequestIdSubscriber;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class Outbox
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private ?RequestStack $requestStack = null,
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
        $request = $this->requestStack?->getCurrentRequest();
        $requestId = $request?->attributes->get(RequestIdSubscriber::ATTRIBUTE);
        if (!\is_string($requestId) || '' === $requestId) {
            return $payload;
        }

        $metadata = \is_array($payload['_meta'] ?? null) ? $payload['_meta'] : [];
        $metadata['requestId'] = $requestId;
        $payload['_meta'] = $metadata;

        return $payload;
    }
}
