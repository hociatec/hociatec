<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Module\Outbox\Domain\Entity\OutboxEvent;

interface OutboxEventStore
{
    /** @return list<OutboxEvent> */
    public function findDue(int $limit): array;
}
