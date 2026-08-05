<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Port;

use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\LockMode;

interface VoucherRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Voucher;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Voucher>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<Voucher> */
    public function findActiveForDate(\DateTimeImmutable $now): array;

    public function findOneByCode(?string $code): ?Voucher;

    public function save(Voucher $voucher): void;

    /** @return list<Voucher> */
    public function findByRecipientUserId(int $userId, int $limit = 20, int $offset = 0): array;

    public function countByRecipientUserId(int $userId): int;
}
