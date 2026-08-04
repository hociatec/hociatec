<?php

declare(strict_types=1);

namespace App\Module\Auth\Repository;

use App\Module\Auth\Entity\RefreshToken;
use App\Module\User\Entity\User;
use Doctrine\DBAL\LockMode;
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

    public function findOneBySelectorForUpdate(string $selector): ?RefreshToken
    {
        return $this->createQueryBuilder('refreshToken')
            ->andWhere('refreshToken.selector = :selector')
            ->setParameter('selector', $selector)
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
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

    public function purgeExpiredOrRevokedBefore(\DateTimeImmutable $now, \DateTimeImmutable $revokedBefore): int
    {
        return (int) $this->createQueryBuilder('refreshToken')
            ->delete()
            ->where('refreshToken.expiresAt < :now')
            ->orWhere('refreshToken.revokedAt IS NOT NULL AND refreshToken.revokedAt < :revokedBefore')
            ->setParameter('now', $now)
            ->setParameter('revokedBefore', $revokedBefore)
            ->getQuery()
            ->execute();
    }
}
