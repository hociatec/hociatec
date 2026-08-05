<?php

declare(strict_types=1);

namespace App\Module\Voucher\Infrastructure\Repository;

use App\Module\Voucher\Application\Port\VoucherLookupPort;
use App\Module\Voucher\Domain\Entity\Voucher;

interface VoucherLookupInterface extends VoucherLookupPort
{
    public function findOneByCode(?string $code): ?Voucher;
}
