<?php

declare(strict_types=1);

namespace App\Module\Voucher\Infrastructure\Repository;

use App\Module\Voucher\Application\Port\VoucherLookupPort;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voucher>
 */
final class VoucherRepository extends ServiceEntityRepository implements VoucherRepositoryPort, VoucherLookupPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voucher::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Voucher
    {
        $voucher = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $voucher instanceof Voucher ? $voucher : null;
    }

    /**
     * @return list<Voucher>
     */
    public function findActiveForDate(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.isActive = :active')
            ->andWhere('v.startsAt IS NULL OR v.startsAt <= :now')
            ->andWhere('v.endsAt IS NULL OR v.endsAt >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('v.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCode(?string $code): ?Voucher
    {
        $normalized = is_string($code) ? mb_strtoupper(trim($code)) : '';
        if ('' === $normalized) {
            return null;
        }

        return $this->createQueryBuilder('v')
            ->andWhere('v.code = :code')
            ->setParameter('code', $normalized)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Voucher $voucher): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($voucher);
    }

    /**
     * @return list<Voucher>
     */
    public function findByRecipientUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.recipientUserId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('v.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countByRecipientUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.recipientUserId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
