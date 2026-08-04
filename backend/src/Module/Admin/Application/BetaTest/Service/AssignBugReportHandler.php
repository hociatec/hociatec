<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Module\BetaTest\Application\Service\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class AssignBugReportHandler
{
    public function __construct(
        private DoctrineUnitOfWork $persistence,
        private BugReportActivityLogger $activityLogger,
        private BugReportAccessPolicy $accessPolicy,
    ) {
    }

    public function assign(BugReport $report, ?User $assignedTo, ?User $actor): void
    {
        if (null !== $assignedTo && !$this->accessPolicy->isAdmin($assignedTo)) {
            throw new \InvalidArgumentException('Administrateur introuvable.');
        }

        $previous = $report->getAssignedTo()?->getEmail();
        $report->assignTo($assignedTo);
        $this->activityLogger->log($report, $actor, 'assignment_changed', $previous, $assignedTo?->getEmail());
        $this->persistence->commit();
    }
}
