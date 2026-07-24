<?php

declare(strict_types=1);

namespace App\Module\Training\DTO;

use App\Module\Training\Entity\TrainingEnrollment;

final readonly class TrainingEnrollmentCheckoutResult
{
    public function __construct(
        public TrainingEnrollment $enrollment,
        public bool $created,
        public ?string $checkoutUrl = null,
    ) {
    }
}
