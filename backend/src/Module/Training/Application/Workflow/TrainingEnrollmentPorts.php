<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Workflow;

use App\Module\Training\Application\Mapper\TrainingSlotValidator;
use App\Module\Training\Application\Port\TrainingEnrollmentRepositoryPort;
use App\Module\Training\Application\Port\TrainingSessionRepositoryPort;

final readonly class TrainingEnrollmentPorts
{
    public function __construct(
        public TrainingSessionRepositoryPort $sessions,
        public TrainingEnrollmentRepositoryPort $enrollments,
        public TrainingSlotValidator $slots,
    ) {
    }
}
