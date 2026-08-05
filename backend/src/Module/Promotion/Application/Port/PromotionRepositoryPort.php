<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Port;

use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;

interface PromotionRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Promotion;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Promotion>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<Promotion> */
    public function findActiveForDate(\DateTimeImmutable $now): array;

    /** @return array{ordersCount:int,lastOrderAt:\DateTimeImmutable|null} */
    public function findUserOrderStats(User $user): array;
}
