<?php

declare(strict_types=1);

namespace App\Module\Support\DTO;

final readonly class SupportReplyData
{
    public function __construct(public string $message, public ?string $subject, public ?string $status)
    {
    }
}
