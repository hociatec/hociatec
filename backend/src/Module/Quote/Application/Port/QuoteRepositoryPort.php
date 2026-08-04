<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Port;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Domain\Entity\Quote;
use Doctrine\DBAL\LockMode;

interface QuoteRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function findConvertedQuoteForOrder(Order $order): ?Quote;

    public function countForYear(int $year): int;

    /** @return list<Quote> */
    public function findAcceptedWaitingForConversion(int $limit = 10): array;

    /**
     * @param list<string> $statusCodes
     *
     * @return list<Quote>
     */
    public function findRecentByStatuses(array $statusCodes, int $limit = 10): array;

    /** @return list<Quote> */
    public function findRecentlyEmailed(int $limit = 6): array;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Quote>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
}
