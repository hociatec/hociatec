<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Repository;

use App\Module\Audit\Application\Port\AuditChecklistItemRepositoryPort;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditChecklistItem>
 */
class AuditChecklistItemRepository extends ServiceEntityRepository implements AuditChecklistItemRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditChecklistItem::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?AuditChecklistItem
    {
        $item = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $item instanceof AuditChecklistItem ? $item : null;
    }
}
