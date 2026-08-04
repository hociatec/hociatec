<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Port;

use App\Module\Voucher\Domain\Entity\Voucher;

interface VoucherRepositoryPort
{
    /** @return list<Voucher> */
    public function findActiveForDate(\DateTimeImmutable $now): array;

    public function findOneByCode(?string $code): ?Voucher;

    public function save(Voucher $voucher): void;

    /** @return list<Voucher> */
    public function findByRecipientUserId(int $userId): array;
}
