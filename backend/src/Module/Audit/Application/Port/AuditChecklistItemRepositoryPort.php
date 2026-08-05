<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Port;

use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use Doctrine\DBAL\LockMode;

interface AuditChecklistItemRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?AuditChecklistItem;
}
