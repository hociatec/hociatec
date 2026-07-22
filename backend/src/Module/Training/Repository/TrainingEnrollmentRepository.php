<?php

declare(strict_types=1);

namespace App\Module\Training\Repository;

use App\Module\Training\Entity\TrainingEnrollment;
use App\Module\Training\Entity\TrainingSession;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TrainingEnrollment> */
class TrainingEnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingEnrollment::class);
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
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }
}
