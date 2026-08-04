<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Workflow;

use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class BugReportActivityLogger
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function log(BugReport $report, ?User $actor, string $action, ?string $fromValue = null, ?string $toValue = null, ?string $message = null): void
    {
        $this->persistence->persist(new BugReportActivity($report, $actor, $action, $fromValue, $toValue, $message));
    }
}
