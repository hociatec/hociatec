<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

final readonly class OutboxMetrics
{
    public function __construct(
        public int $pendingEvents,
        public ?int $oldestPendingAgeSeconds,
        public int $failedEvents,
        public int $staleProcessingEvents,
        public int $deadEvents,
    ) {
    }

    /** @return array<string, int|null> */
    public function toArray(): array
    {
        return [
            'pendingEvents' => $this->pendingEvents,
            'oldestPendingAgeSeconds' => $this->oldestPendingAgeSeconds,
            'failedEvents' => $this->failedEvents,
            'staleProcessingEvents' => $this->staleProcessingEvents,
            'deadEvents' => $this->deadEvents,
        ];
    }
}
