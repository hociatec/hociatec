<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\Policy;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;

final readonly class VoucherEligibilityPolicy
{
    public function canBeUsedBy(Voucher $voucher, ?User $user, \DateTimeImmutable $now): bool
    {
        return $voucher->isActive()
            && $voucher->validityPeriod()->hasStartedAt($now)
            && !$voucher->validityPeriod()->isExpiredAt($now)
            && $voucher->recipientConstraint()->matches($user);
    }
}
