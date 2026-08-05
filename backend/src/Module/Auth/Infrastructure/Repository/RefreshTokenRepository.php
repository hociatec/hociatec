<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Repository;

use App\Module\Auth\Application\Port\RefreshTokenRepositoryPort;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function save(RefreshToken $refreshToken): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($refreshToken);
    }

    public function remove(RefreshToken $refreshToken): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($refreshToken);
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

    public function revokeAllForUser(User $user): void
    {
        $tokens = $this->findBy(['user' => $user, 'revokedAt' => null]);

        foreach ($tokens as $token) {
            $token->revoke();
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

    public function revokeActiveTokensOverLimit(User $user, int $limit): int
    {
        if (1 > $limit) {
            throw new \InvalidArgumentException('La limite de sessions doit être positive.');
        }

        $tokensToRevoke = $this->createQueryBuilder('refreshToken')
            ->andWhere('refreshToken.user = :user')
            ->andWhere('refreshToken.revokedAt IS NULL')
            ->andWhere('refreshToken.expiresAt > :now')
            ->orderBy('refreshToken.createdAt', 'DESC')
            ->addOrderBy('refreshToken.id', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->setFirstResult($limit)
            ->getQuery()
            ->getResult();

        foreach ($tokensToRevoke as $token) {
            $token->revoke();
        }

        return count($tokensToRevoke);
    }
}
