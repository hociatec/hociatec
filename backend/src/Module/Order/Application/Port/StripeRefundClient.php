<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

interface StripeRefundClient
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createRefund(array $payload, ?string $idempotencyKey = null): array;
}
