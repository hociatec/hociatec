<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Application\Workflow\BugReportActivityLogger;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class AssignBugReportHandler
{
    public function __construct(
        private UnitOfWork $persistence,
        private BugReportActivityLogger $activityLogger,
    ) {
    }

    public function assign(BugReport $report, ?User $assignedTo, ?User $actor): void
    {
        if (null !== $assignedTo && !$assignedTo->isAdmin()) {
            throw new \InvalidArgumentException('Administrateur introuvable.');
        }

        $previous = $report->getAssignedTo()?->getEmail();
        $report->assignTo($assignedTo);
        $this->activityLogger->log($report, $actor, 'assignment_changed', $previous, $assignedTo?->getEmail());
        $this->persistence->commit();
    }
}
