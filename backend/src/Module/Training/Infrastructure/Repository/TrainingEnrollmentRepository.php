<?php

declare(strict_types=1);

namespace App\Module\Training\Infrastructure\Repository;

use App\Module\Training\Application\Port\TrainingEnrollmentRepositoryPort;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TrainingEnrollment> */
class TrainingEnrollmentRepository extends ServiceEntityRepository implements TrainingEnrollmentRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingEnrollment::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TrainingEnrollment
    {
        $enrollment = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $enrollment instanceof TrainingEnrollment ? $enrollment : null;
    }

    public function countActiveForSession(TrainingSession $session): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.session = :session')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('session', $session)
            ->setParameter('statuses', [
                TrainingEnrollment::STATUS_PAID,
                TrainingEnrollment::STATUS_CONFIRMED,
                TrainingEnrollment::STATUS_COMPLETED,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveForSessionSlot(TrainingSession $session, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.session = :session')
            ->andWhere('e.status IN (:statuses)')
            ->andWhere('e.scheduledStartsAt < :endsAt')
            ->andWhere('e.scheduledEndsAt > :startsAt')
            ->setParameter('session', $session)
            ->setParameter('statuses', [
                TrainingEnrollment::STATUS_PAID,
                TrainingEnrollment::STATUS_CONFIRMED,
                TrainingEnrollment::STATUS_COMPLETED,
            ])
            ->setParameter('startsAt', $startsAt)
            ->setParameter('endsAt', $endsAt)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneForUserAndSession(User $user, TrainingSession $session): ?TrainingEnrollment
    {
        return $this->findOneBy(['user' => $user, 'session' => $session]);
    }

    public function findOneByStripeSessionId(string $stripeSessionId): ?TrainingEnrollment
    {
        return $this->findOneBy(['stripeSessionId' => $stripeSessionId]);
    }

    /** @return list<TrainingEnrollment> */
    public function findForUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC'], max(1, min(100, $limit)), max(0, $offset));
    }

    public function countForUser(User $user): int
    {
        return $this->count(['user' => $user]);
    }
}
