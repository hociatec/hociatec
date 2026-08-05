<?php

declare(strict_types=1);

namespace App\Module\Promotion\Infrastructure\Repository;

use App\Module\Promotion\Application\Port\PromotionRepositoryPort;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository implements PromotionRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Promotion
    {
        $promotion = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $promotion instanceof Promotion ? $promotion : null;
    }

    /**
     * @return list<Promotion>
     */
    public function findActiveForDate(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startsAt IS NULL OR p.startsAt <= :now')
            ->andWhere('p.endsAt IS NULL OR p.endsAt >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array{ordersCount: int, lastOrderAt: \DateTimeImmutable|null} */
    public function findUserOrderStats(User $user): array
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(o.id) AS ordersCount', 'MAX(o.createdAt) AS lastOrderAt')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        $lastOrderAt = match (true) {
            $result['lastOrderAt'] instanceof \DateTimeImmutable => $result['lastOrderAt'],
            $result['lastOrderAt'] instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($result['lastOrderAt']),
            is_string($result['lastOrderAt'] ?? null) && '' !== trim($result['lastOrderAt']) => new \DateTimeImmutable($result['lastOrderAt']),
            default => null,
        };

        return ['ordersCount' => (int) ($result['ordersCount'] ?? 0), 'lastOrderAt' => $lastOrderAt];
    }
}
