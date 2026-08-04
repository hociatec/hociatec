<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Port;

use App\Module\Audit\Domain\Entity\AuditRequest;

interface AuditPdfRenderer
{
    public function renderDetailed(AuditRequest $audit): string;

    public function renderSummary(AuditRequest $audit): string;
}
