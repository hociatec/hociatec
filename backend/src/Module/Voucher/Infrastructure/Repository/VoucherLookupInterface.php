<?php

declare(strict_types=1);

namespace App\Module\Voucher\Infrastructure\Repository;

use App\Module\Voucher\Domain\Entity\Voucher;

interface VoucherLookupInterface
{
    public function findOneByCode(?string $code): ?Voucher;
}
