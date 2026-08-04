<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

final readonly class RefundUpdateData
{
    public function __construct(public ?string $status, public ?string $stripeRefundId, public ?string $internalNotes)
    {
    }
}
