<?php

declare(strict_types=1);

namespace App\Shared\Outbox;

use App\Shared\Outbox\Entity\OutboxEvent;

interface OutboxEventHandler
{
    public function supports(OutboxEvent $event): bool;

    public function handle(OutboxEvent $event): void;
}
