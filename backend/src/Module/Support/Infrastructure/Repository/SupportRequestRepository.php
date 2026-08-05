<?php

declare(strict_types=1);

namespace App\Module\Support\Infrastructure\Repository;

use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportRequest>
 */
final class SupportRequestRepository extends ServiceEntityRepository implements SupportRequestRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportRequest::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);
    }
}
