<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

use App\Module\Outbox\Domain\Entity\OutboxEvent;

interface OutboxEventHandler
{
    public function supports(OutboxEvent $event): bool;

    public function handle(OutboxEvent $event): void;
}
