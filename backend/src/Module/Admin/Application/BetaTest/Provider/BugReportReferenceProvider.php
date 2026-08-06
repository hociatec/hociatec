<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Provider;

use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BugReport;

final readonly class BugReportReferenceProvider
{
    public function __construct(private BugReportRepositoryPort $reports)
    {
    }

    public function referenceReport(int $id, int $currentReportId): BugReport
    {
        if ($id <= 0 || $id === $currentReportId) {
            throw new \InvalidArgumentException('Sélectionnez un autre signalement de référence.');
        }

        $report = $this->reports->find($id);
        if (!$report instanceof BugReport) {
            throw new \RuntimeException('Signalement de référence introuvable.');
        }

        return $report;
    }
}
