<?php

declare(strict_types=1);

namespace App\Module\Voucher\Repository;

use App\Module\Voucher\Entity\Voucher;

interface VoucherLookupInterface
{
    public function findOneByCode(?string $code): ?Voucher;
}
