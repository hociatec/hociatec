<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Port;

use App\Module\Support\Domain\Entity\SupportRequest;
use App\Shared\Application\LockMode;

interface SupportRequestRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<SupportRequest>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;
}
