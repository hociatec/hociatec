<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\User\Domain\Entity\User;

interface OrderItemRepositoryPort
{
    public function findById(int $id): ?OrderItem;

    public function findAdminRentalById(int $id): ?OrderItem;

    /** @return list<OrderItem> */
    public function findPendingReviewItemsForUser(User $user, int $limit = 20, int $offset = 0): array;

    public function countPendingReviewItemsForUser(User $user): int;

    /** @return list<OrderItem> */
    public function findUpcomingRentalsForUser(User $user, \DateTimeImmutable $today, int $limit = 20, int $offset = 0): array;

    /** @return list<OrderItem> */
    public function findPastRentalsForUser(User $user, \DateTimeImmutable $today, int $limit = 20, int $offset = 0): array;

    public function countUpcomingRentalsForUser(User $user, \DateTimeImmutable $today): int;

    public function countPastRentalsForUser(User $user, \DateTimeImmutable $today): int;

    /** @return list<OrderItem> */
    public function findRentalsForAdmin(
        ?string $search,
        ?string $timeline,
        ?string $requestStatus,
        ?string $requestType,
        \DateTimeImmutable $today,
        int $limit = 20,
        int $offset = 0,
    ): array;

    public function countRentalsForAdmin(
        ?string $search,
        ?string $timeline,
        ?string $requestStatus,
        ?string $requestType,
        \DateTimeImmutable $today,
    ): int;
}
