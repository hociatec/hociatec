<?php

declare(strict_types=1);

namespace App\Shared\Outbox;

use App\Shared\Outbox\Entity\OutboxEvent;

interface OutboxEventStore
{
    /** @return list<OutboxEvent> */
    public function findDue(int $limit): array;
}
