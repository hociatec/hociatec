<?php

declare(strict_types=1);

namespace App\Module\Auth\Repository;

use App\Module\Auth\Entity\RefreshToken;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function save(RefreshToken $refreshToken, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($refreshToken);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function remove(RefreshToken $refreshToken, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($refreshToken);

        if ($flush) {
            $entityManager->flush();
        }
    }

    public function findOneBySelector(string $selector): ?RefreshToken
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    public function revokeAllForUser(User $user, bool $flush = false): void
    {
        $tokens = $this->findBy(['user' => $user, 'revokedAt' => null]);

        foreach ($tokens as $token) {
            $token->revoke();
        }

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
