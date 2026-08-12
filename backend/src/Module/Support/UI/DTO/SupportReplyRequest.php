<?php

declare(strict_types=1);

namespace App\Module\Support\UI\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SupportReplyRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $message,
        #[Assert\Length(max: 255)]
        public ?string $subject,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['message'] ?? null) ? trim($payload['message']) : '',
            is_string($payload['subject'] ?? null) ? trim($payload['subject']) : null,
        );
    }
}
