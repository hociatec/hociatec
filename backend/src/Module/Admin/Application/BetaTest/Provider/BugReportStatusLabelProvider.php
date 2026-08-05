<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Provider;

final readonly class BugReportStatusLabelProvider
{
    public function label(string $status): string
    {
        return [
            'submitted' => 'Soumis',
            'under_review' => 'En cours d’analyse',
            'need_info' => 'Informations nécessaires',
            'planned' => 'Correction planifiée',
            'resolved' => 'Corrigé',
            'duplicate' => 'Doublon',
            'rejected' => 'Rejeté',
        ][$status] ?? $status;
    }
}
