<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Repository;

use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TradeInRequest> */
final class TradeInRequestRepository extends ServiceEntityRepository implements TradeInRequestRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradeInRequest::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TradeInRequest
    {
        $request = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $request instanceof TradeInRequest ? $request : null;
    }

    /** @return list<TradeInRequest> */
    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('r');
        $this->filterByUserIdentity($qb, $user);

        return $qb
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        $qb = $this->createQueryBuilder('r')->select('COUNT(r.id)');
        $this->filterByUserIdentity($qb, $user);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<TradeInRequest> */
    public function findForAdmin(?string $search = null, ?TradeInStatus $status = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createAdminQuery($search, $status)->orderBy('r.createdAt', 'DESC');
        $qb->setFirstResult(max(0, $offset))->setMaxResults(max(1, min(100, $limit)));

        return $qb->getQuery()->getResult();
    }

    public function countForAdmin(?string $search = null, ?TradeInStatus $status = null): int
    {
        return (int) $this->createAdminQuery($search, $status)
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<TradeInRequest> */
    public function findClosedWithExpiredPrivateDocuments(\DateTimeImmutable $closedBefore, int $limit = 100): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.closedAt IS NOT NULL')
            ->andWhere('r.closedAt < :closedBefore')
            ->andWhere('(r.ribPath IS NOT NULL OR r.receiptPath IS NOT NULL)')
            ->setParameter('closedBefore', $closedBefore)
            ->orderBy('r.closedAt', 'ASC')
            ->setMaxResults(max(1, min(500, $limit)))
            ->getQuery()
            ->getResult();
    }

    private function createAdminQuery(?string $search = null, ?TradeInStatus $status = null): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('r');
        $searchPattern = LikeSearchHelper::containsPattern($search);
        if (null !== $searchPattern) {
            $qb->andWhere('r.reference LIKE :search OR r.email LIKE :search OR r.productName LIKE :search')->setParameter('search', $searchPattern);
        }
        if (null !== $status) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        return $qb;
    }

    private function filterByUserIdentity(\Doctrine\ORM\QueryBuilder $qb, User $user): void
    {
        $userId = $user->getId();
        if (null !== $userId) {
            $qb
                ->andWhere('r.userId = :userId OR r.user = :user')
                ->setParameter('userId', $userId)
                ->setParameter('user', $user);

            return;
        }

        $qb->andWhere('r.user = :user')->setParameter('user', $user);
    }

    public function delete(TradeInRequest $request): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($request);
    }
}
