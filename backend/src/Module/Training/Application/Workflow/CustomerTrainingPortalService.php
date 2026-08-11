<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Workflow;

use App\Module\Training\Application\Port\TrainingEnrollmentRepositoryPort;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerTrainingPortalService
{
    public function __construct(
        private TrainingEnrollmentRepositoryPort $enrollments,
        private TrainingFormatter $formatter,
    ) {
    }

    /**
     * @return array{items:list<array<string,mixed>>, total:int}
     */
    public function listEnrollmentsForUser(User $user, int $limit, int $offset): array
    {
        $items = $this->enrollments->findForUser($user, $limit, $offset);

        return [
            'items' => array_map(fn (TrainingEnrollment $enrollment): array => $this->formatter->formatEnrollment($enrollment), $items),
            'total' => $this->enrollments->countForUser($user),
        ];
    }
}
