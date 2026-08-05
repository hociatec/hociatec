<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Port;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;

interface AuditRequestRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?AuditRequest;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<AuditRequest>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<AuditRequest> */
    public function findByUser(User $user): array;
}
