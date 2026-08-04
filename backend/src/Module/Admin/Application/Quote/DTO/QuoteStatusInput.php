<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

use App\Module\Quote\Domain\Entity\Quote;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class QuoteStatusInput
{
    public function __construct(#[Assert\Choice(choices: [Quote::STATUS_DRAFT, Quote::STATUS_SENT, Quote::STATUS_ACCEPTED, Quote::STATUS_REFUSED, Quote::STATUS_EXPIRED, 'brouillon', 'envoyé', 'accepté', 'refusé', 'expiré'])] public string $status)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['status'] ?? null) ? trim($payload['status']) : '');
    }
}
