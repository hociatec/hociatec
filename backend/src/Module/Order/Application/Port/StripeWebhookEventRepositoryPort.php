<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\StripeWebhookEvent;

interface StripeWebhookEventRepositoryPort
{
    public function findOneByStripeEventId(string $eventId): ?StripeWebhookEvent;
}
