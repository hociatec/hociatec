<?php

declare(strict_types=1);

namespace App\Module\Training\Infrastructure\Repository;

use App\Module\Training\Application\Port\TrainingSessionRepositoryPort;

use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TrainingSession> */
class TrainingSessionRepository extends ServiceEntityRepository implements TrainingSessionRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingSession::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TrainingSession
    {
        $session = parent::find($id, $lockMode, $lockVersion);

        return $session instanceof TrainingSession ? $session : null;
    }

    /** @return list<TrainingSession> */
    public function findUpcomingForTraining(Training $training): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.training = :training')
            ->andWhere('s.endsAt >= :now')
            ->andWhere('s.status = :status')
            ->setParameter('training', $training)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', 'scheduled')
            ->orderBy('s.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForUpdate(int $id): ?TrainingSession
    {
        $session = $this->find($id, LockMode::PESSIMISTIC_WRITE);

        return $session instanceof TrainingSession ? $session : null;
    }
}
