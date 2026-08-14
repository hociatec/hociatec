<?php

declare(strict_types=1);

namespace App\Module\Appointment\Infrastructure\Repository;

use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findBetween(\DateTimeImmutable $start, \DateTimeImmutable $end, ?Appointment $ignoredAppointment = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.startAt < :end')
            ->andWhere('a.endAt > :start')
            ->andWhere('a.status != :cancelledStatus')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('cancelledStatus', Appointment::STATUS_CANCELLED)
            ->orderBy('a.startAt', 'ASC');

        if ($ignoredAppointment?->getId() !== null) {
            $qb
                ->andWhere('a.id != :ignoredAppointmentId')
                ->setParameter('ignoredAppointmentId', $ignoredAppointment->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findForUser(User $user, ?string $status = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.startAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)));

        if (null !== $status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return list<Appointment> */
    public function findUpcomingForUser(User $user, \DateTimeImmutable $now, int $limit = 20, int $offset = 0): array
    {
        return $this->createUserPeriodQuery($user, $now, true)
            ->orderBy('a.startAt', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countUpcomingForUser(User $user, \DateTimeImmutable $now): int
    {
        return $this->countUserPeriod($user, $now, true);
    }

    /** @return list<Appointment> */
    public function findPastForUser(User $user, \DateTimeImmutable $now, int $limit = 20, int $offset = 0): array
    {
        return $this->createUserPeriodQuery($user, $now, false)
            ->orderBy('a.startAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countPastForUser(User $user, \DateTimeImmutable $now): int
    {
        return $this->countUserPeriod($user, $now, false);
    }

    private function countUserPeriod(User $user, \DateTimeImmutable $now, bool $upcoming): int
    {
        return (int) $this->createUserPeriodQuery($user, $now, $upcoming)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createUserPeriodQuery(User $user, \DateTimeImmutable $now, bool $upcoming): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->setParameter('cancelledStatus', Appointment::STATUS_CANCELLED);

        $qb->andWhere(
            $upcoming
                ? '(a.startAt >= :now AND a.status != :cancelledStatus)'
                : '(a.startAt < :now OR a.status = :cancelledStatus)',
        );

        return $qb;
    }
}
