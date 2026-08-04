<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

final readonly class OutboxAlertPolicy
{
    public function alertFor(OutboxMetrics $metrics): ?OutboxAlert
    {
        if ($metrics->staleProcessingEvents > 0) {
            return new OutboxAlert('critical', 'Outbox events are stuck in processing.', $metrics);
        }

        if ($metrics->deadEvents > 0) {
            return new OutboxAlert('critical', 'Outbox events reached the dead-letter queue.', $metrics);
        }

        if ($metrics->failedEvents > 0) {
            return new OutboxAlert('warning', 'Outbox events are failing and waiting for retry.', $metrics);
        }

        return null;
    }
}
