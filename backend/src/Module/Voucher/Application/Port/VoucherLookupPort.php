<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Port;

use App\Module\Voucher\Domain\Entity\Voucher;

interface VoucherLookupPort
{
    public function findOneByCode(?string $code): ?Voucher;
}
