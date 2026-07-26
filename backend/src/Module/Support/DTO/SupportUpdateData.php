<?php

declare(strict_types=1);

namespace App\Module\Support\DTO;

final readonly class SupportUpdateData
{
    public function __construct(public ?string $status, public ?string $internalNotes, public ?string $subject)
    {
    }
}
