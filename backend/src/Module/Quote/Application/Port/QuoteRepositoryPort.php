<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Port;

use App\Module\Quote\Domain\Entity\Quote;
use App\Shared\Application\LockMode;

interface QuoteRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Quote;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?Quote;

    /** @return list<Quote> */
    public function findBySearch(?string $search, ?string $statusCode, int $limit = 20, int $offset = 0, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array;

    public function countBySearch(?string $search, ?string $statusCode, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): int;

    /** @return list<Quote> */
    public function findByCustomerEmail(string $email, int $limit = 20, int $offset = 0): array;

    public function countByCustomerEmail(string $email): int;

    public function findConvertedQuoteForOrder(int $orderId): ?Quote;

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
