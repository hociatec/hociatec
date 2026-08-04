<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\ValueObject;

use App\Module\User\Domain\Entity\User;

final readonly class VoucherRecipientConstraint
{
    public function __construct(
        public ?int $userId,
        public ?string $email,
    ) {
    }

    public function exists(): bool
    {
        return null !== $this->userId || null !== $this->email;
    }

    public function matches(?User $user): bool
    {
        if (!$this->exists()) {
            return true;
        }

        if (null === $user) {
            return false;
        }

        if (null !== $this->userId) {
            return $this->userId === $user->getId();
        }

        return null !== $this->email && 0 === strcasecmp($this->email, $user->getEmail());
    }
}
