<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Port;

use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;

interface BugReportActivityRepositoryPort
{
    /** @return list<BugReportActivity> */
    public function findForReport(BugReport $report): array;
}
