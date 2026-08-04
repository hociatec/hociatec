<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

final readonly class OutboxAlert
{
    public function __construct(
        public string $severity,
        public string $message,
        public OutboxMetrics $metrics,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'message' => $this->message,
            'metrics' => $this->metrics->toArray(),
        ];
    }
}
