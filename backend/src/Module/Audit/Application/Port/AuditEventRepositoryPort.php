<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Port;

use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;

interface AuditEventRepositoryPort
{
    /** @return list<AuditEvent> */
    public function findByAudit(AuditRequest $audit, string $order = 'DESC'): array;
}
