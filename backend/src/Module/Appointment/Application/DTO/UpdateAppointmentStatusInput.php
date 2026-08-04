<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\DTO;

use App\Module\Appointment\Domain\Entity\Appointment;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAppointmentStatusInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [Appointment::STATUS_CONFIRMED, Appointment::STATUS_CANCELLED])]
        public string $status,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['status'] ?? null) ? trim($payload['status']) : '');
    }
}
