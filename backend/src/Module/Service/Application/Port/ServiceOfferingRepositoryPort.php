<?php

declare(strict_types=1);

namespace App\Module\Service\Application\Port;

use App\Module\Service\Domain\Entity\ServiceOffering;
use App\Shared\Application\LockMode;

interface ServiceOfferingRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?ServiceOffering;

    /** @return list<ServiceOffering> */
    public function findPaginated(int $limit, int $offset): array;

    /** @return list<ServiceOffering> */
    public function findPublic(?string $search, int $limit, int $offset): array;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<ServiceOffering>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<ServiceOffering> */
    public function findForAdmin(?string $search, int $limit, int $offset): array;

    public function countForAdmin(?string $search): int;

    public function countPublic(?string $search): int;

    public function countAll(): int;

    public function delete(ServiceOffering $service): void;
}
