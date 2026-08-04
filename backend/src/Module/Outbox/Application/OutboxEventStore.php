<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Module\Outbox\Domain\Entity\OutboxEvent;

interface OutboxEventStore
{
    /** @return list<OutboxEvent> */
    public function findDueForUpdate(int $limit): array;

    public function recoverStaleProcessing(\DateTimeImmutable $threshold): int;

    public function metricsSnapshot(\DateTimeImmutable $staleProcessingThreshold): OutboxMetrics;

    public function purgeFinalizedBefore(\DateTimeImmutable $threshold): int;
}
