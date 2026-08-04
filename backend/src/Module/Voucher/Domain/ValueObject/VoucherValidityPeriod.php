<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\ValueObject;

final readonly class VoucherValidityPeriod
{
    public function __construct(
        public ?\DateTimeImmutable $startsAt,
        public ?\DateTimeImmutable $endsAt,
    ) {
        if (null !== $startsAt && null !== $endsAt && $startsAt >= $endsAt) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }
    }

    public function hasStartedAt(\DateTimeImmutable $now): bool
    {
        return null === $this->startsAt || $this->startsAt <= $now;
    }

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return null !== $this->endsAt && $this->endsAt < $now;
    }
}
