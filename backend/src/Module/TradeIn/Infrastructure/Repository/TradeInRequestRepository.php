<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Repository;

use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
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
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')->andWhere('r.user = :user')->setParameter('user', $user)->orderBy('r.createdAt', 'DESC')->getQuery()->getResult();
    }

    /** @return list<TradeInRequest> */
    public function findForAdmin(?string $search = null, ?TradeInStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('r')->orderBy('r.createdAt', 'DESC');
        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('r.reference LIKE :search OR r.email LIKE :search OR r.productName LIKE :search')->setParameter('search', '%'.trim($search).'%');
        }
        if (null !== $status) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function delete(TradeInRequest $request): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($request);
    }
}
