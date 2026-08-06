<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\StripeWebhookEvent;

interface StripeWebhookEventPersistencePort
{
    public function save(StripeWebhookEvent $event): void;

    public function commit(): void;
}
