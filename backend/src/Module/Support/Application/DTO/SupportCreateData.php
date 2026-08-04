<?php

declare(strict_types=1);

namespace App\Module\Support\Application\DTO;

final readonly class SupportCreateData
{
    public function __construct(public int $customerId, public string $subject, public string $reason, public ?string $message, public ?string $internalNotes, public ?int $orderId)
    {
    }
}
