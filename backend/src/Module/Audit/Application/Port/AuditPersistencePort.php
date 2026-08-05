<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Port;

use App\Module\Audit\Domain\Entity\AuditRequest;

interface AuditPersistencePort
{
    public function save(AuditRequest $audit): void;
    public function commit(): void;
}
