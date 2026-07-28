<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Service;

use App\Module\BetaTest\Entity\BugReport;
use App\Module\BetaTest\Entity\BugReportActivity;
use App\Module\User\Entity\User;
use App\Shared\Persistence\DoctrinePersistence;

final readonly class BugReportActivityLogger
{
    public function __construct(private DoctrinePersistence $persistence)
    {
    }

    public function log(BugReport $report, ?User $actor, string $action, ?string $fromValue = null, ?string $toValue = null, ?string $message = null): void
    {
        $this->persistence->persist(new BugReportActivity($report, $actor, $action, $fromValue, $toValue, $message));
    }
}
