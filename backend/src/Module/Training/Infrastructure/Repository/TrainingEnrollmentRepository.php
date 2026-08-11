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

    public function countActiveForSessions(array $sessions): array
    {
        $sessionIds = array_values(array_filter(
            array_map(static fn (TrainingSession $session): ?int => $session->getId(), $sessions),
            static fn (?int $id): bool => null !== $id,
        ));
        if ([] === $sessionIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.session) AS sessionId')
            ->addSelect('COUNT(e.id) AS enrolledCount')
            ->andWhere('e.session IN (:sessionIds)')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('sessionIds', $sessionIds)
            ->setParameter('statuses', [
                TrainingEnrollment::STATUS_PAID,
                TrainingEnrollment::STATUS_CONFIRMED,
                TrainingEnrollment::STATUS_COMPLETED,
            ])
            ->groupBy('e.session')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $sessionId = (int) ($row['sessionId'] ?? 0);
            if ($sessionId > 0) {
                $counts[$sessionId] = (int) ($row['enrolledCount'] ?? 0);
            }
        }

        return $counts;
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
        return $this->createQueryBuilder('e')
            ->addSelect('s', 't', 'r')
            ->join('e.session', 's')
            ->join('s.training', 't')
            ->leftJoin('t.roadmapItems', 'r')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, min(100, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function countForUser(User $user): int
    {
        return $this->count(['user' => $user]);
    }
}
