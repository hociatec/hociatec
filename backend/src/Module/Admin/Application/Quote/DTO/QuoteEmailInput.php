<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

use App\Shared\Domain\ValueObject\EmailAddress;

final readonly class QuoteEmailInput
{
    public function __construct(public ?EmailAddress $to = null)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['to'] ?? null) && '' !== trim($payload['to']) ? EmailAddress::fromString($payload['to']) : null);
    }
}
