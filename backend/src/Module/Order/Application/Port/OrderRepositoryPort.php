<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\LockMode;

interface OrderRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    public function findForUpdate(int $id): ?Order;

    public function countForYear(int $year): int;

    public function countInvoicedForYear(int $year): int;

    public function hasActiveForUser(User $user): bool;

    /** @return list<Order> */
    public function findByUser(User $user): array;

    /** @return list<Order> */
    public function findRecentForAdmin(int $limit = 8): array;

    /** @return list<Order> */
    public function findPendingPaymentForAdmin(int $limit = 10): array;

    /** @return list<Order> */
    public function findFulfillmentQueue(int $limit = 30): array;

    /** @return array{count:int,totalCents:int} */
    public function getSummaryBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /** @return array<string,int> */
    public function getStatusCounts(): array;

    public function countWithOperationalIssues(): int;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Order>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
}
