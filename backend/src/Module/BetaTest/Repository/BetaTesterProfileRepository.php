<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Repository;

use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class BetaTesterProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetaTesterProfile::class);
    }

    public function findOneByUser(User $user): ?BetaTesterProfile
    {
        $result = $this->findOneBy(['user' => $user]);

        return $result instanceof BetaTesterProfile ? $result : null;
    }
}
