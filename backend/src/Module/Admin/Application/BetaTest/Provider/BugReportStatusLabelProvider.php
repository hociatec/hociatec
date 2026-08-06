<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Provider;

use App\Module\BetaTest\Domain\Enum\BugReportStatus;

final readonly class BugReportStatusLabelProvider
{
    public function label(string $status): string
    {
        $known = [
            BugReportStatus::SUBMITTED->value => 'Soumis',
            BugReportStatus::UNDER_REVIEW->value => 'En cours d’analyse',
            BugReportStatus::NEED_INFO->value => 'Informations nécessaires',
            BugReportStatus::PLANNED->value => 'Correction planifiée',
            BugReportStatus::RESOLVED->value => 'Corrigé',
            BugReportStatus::DUPLICATE->value => 'Doublon',
            BugReportStatus::REJECTED->value => 'Rejeté',
        ];

        return $known[$status] ?? $status;
    }
}
