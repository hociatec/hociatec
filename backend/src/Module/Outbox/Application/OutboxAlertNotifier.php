<?php

declare(strict_types=1);

namespace App\Module\Outbox\Application;

interface OutboxAlertNotifier
{
    public function notify(OutboxAlert $alert): void;
}
