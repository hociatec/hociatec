<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Port;

use App\Module\Training\Domain\Entity\TrainingCategory;
use Doctrine\DBAL\LockMode;

interface TrainingCategoryRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TrainingCategory;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?TrainingCategory;

    /** @return list<TrainingCategory> */
    public function findOrdered(bool $activeOnly = false): array;
}
