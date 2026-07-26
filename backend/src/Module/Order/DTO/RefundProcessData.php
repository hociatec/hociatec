<?php

declare(strict_types=1);

namespace App\Module\Order\DTO;

final readonly class RefundProcessData
{
    public function __construct(public string $confirmation, public ?string $paymentIntentId)
    {
    }
}
