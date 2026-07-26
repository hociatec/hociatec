<?php

declare(strict_types=1);

namespace App\Module\Order\DTO;

final readonly class RefundCreateData
{
    public function __construct(public int $orderId, public ?int $amountCents, public ?string $reason, public ?string $internalNotes, public ?int $paymentId, public string $currencyCode)
    {
    }
}
