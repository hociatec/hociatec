<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application\Port;

interface OutboxRequestContextPort
{
    public function requestId(): ?string;
}
