<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Port;

use App\Module\Training\Domain\Entity\Training;
use App\Shared\Application\LockMode;

interface TrainingRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Training;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Training>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?Training;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<Training> */
    public function findActive(?string $category = null): array;

    /** @return list<Training> */
    public function findActivePaginated(?string $category, int $limit, int $offset): array;

    public function countActive(?string $category = null): int;

    /** @return list<Training> */
    public function findPublicCatalog(
        ?string $search,
        ?string $category,
        ?string $format,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?int $minDurationMinutes,
        ?int $maxDurationMinutes,
        string $sort,
        int $limit,
        int $offset,
    ): array;

    public function countPublicCatalog(
        ?string $search,
        ?string $category,
        ?string $format,
        ?int $minPriceCents,
        ?int $maxPriceCents,
        ?int $minDurationMinutes,
        ?int $maxDurationMinutes,
    ): int;
}
