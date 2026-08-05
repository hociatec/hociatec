<?php

declare(strict_types=1);

namespace App\Module\Appointment\Infrastructure\Repository;

use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository implements AppointmentRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Appointment
    {
        $appointment = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $appointment instanceof Appointment ? $appointment : null;
    }

    /**
     * @return list<Appointment>
     */
    public function findBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.startAt < :end')
            ->andWhere('a.endAt > :start')
            ->andWhere('a.status != :cancelledStatus')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('cancelledStatus', Appointment::STATUS_CANCELLED)
            ->orderBy('a.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findForUser(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.startAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
