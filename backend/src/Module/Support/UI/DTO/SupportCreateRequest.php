<?php

declare(strict_types=1);

namespace App\Module\Support\UI\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupportCreateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $subject,
        #[Assert\Length(max: 100)]
        public string $reason,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $message,
        #[Assert\Positive]
        public ?int $orderId,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['subject'] ?? null) ? trim($payload['subject']) : 'Demande SAV',
            is_string($payload['reason'] ?? null) ? trim($payload['reason']) : 'other',
            is_string($payload['message'] ?? null) ? trim($payload['message']) : '',
            is_numeric($payload['orderId'] ?? null) ? (int) $payload['orderId'] : null,
        );
    }
}
