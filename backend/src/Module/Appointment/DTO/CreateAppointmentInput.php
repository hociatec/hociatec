<?php

declare(strict_types=1);

namespace App\Module\Appointment\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAppointmentInput
{
    public function __construct(
        #[Assert\Positive]
        public int $prestationId,
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $startAt,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_numeric($payload['prestationId'] ?? null) ? (int) $payload['prestationId'] : 0,
            is_string($payload['startAt'] ?? null) ? trim($payload['startAt']) : '',
        );
    }
}
