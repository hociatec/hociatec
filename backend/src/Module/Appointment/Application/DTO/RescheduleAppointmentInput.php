<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RescheduleAppointmentInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $startAt,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['startAt'] ?? null) ? trim($payload['startAt']) : '');
    }
}
