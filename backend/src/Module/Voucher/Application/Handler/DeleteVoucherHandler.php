<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Handler;

use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteVoucherHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function delete(Voucher $voucher): void
    {
        $this->persistence->remove($voucher);
        $this->persistence->commit();
    }
}
