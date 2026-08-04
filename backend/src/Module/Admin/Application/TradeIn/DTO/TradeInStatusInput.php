<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TradeInStatusInput
{
    public function __construct(#[Assert\Choice(choices: ['submitted', 'under_review', 'offer_sent', 'accepted', 'declined', 'received', 'inspected', 'completed', 'cancelled', 'expired'])] public string $status)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['status'] ?? null) ? trim($payload['status']) : '');
    }
}
