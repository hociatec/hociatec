<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Port;

use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportComment;

interface BugReportCommentRepositoryPort
{
    /** @return list<BugReportComment> */
    public function findForReportPaginated(BugReport $report, int $limit, int $offset): array;

    public function countForReport(BugReport $report): int;
}
