<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Port;

use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Shared\Application\LockMode;

interface TrainingSessionRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TrainingSession;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<TrainingSession>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<TrainingSession> */
    public function findUpcomingForTraining(Training $training): array;

    public function findForUpdate(int $id): ?TrainingSession;
}
