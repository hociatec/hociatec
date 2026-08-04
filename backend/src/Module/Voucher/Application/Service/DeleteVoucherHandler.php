<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Voucher\Domain\Entity\Voucher;

final readonly class DeleteVoucherHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function delete(Voucher $voucher): void
    {
        $this->persistence->remove($voucher);
        $this->persistence->commit();
    }
}
