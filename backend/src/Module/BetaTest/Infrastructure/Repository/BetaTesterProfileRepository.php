<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Repository;

use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;

use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BetaTesterProfile>
 */
final class BetaTesterProfileRepository extends ServiceEntityRepository implements BetaTesterProfileRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetaTesterProfile::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaTesterProfile
    {
        $profile = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $profile instanceof BetaTesterProfile ? $profile : null;
    }

    public function findOneByUser(User $user): ?BetaTesterProfile
    {
        $result = $this->findOneBy(['user' => $user]);

        return $result instanceof BetaTesterProfile ? $result : null;
    }
}
