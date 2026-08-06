<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Workflow;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;
use Psr\Clock\ClockInterface;

final readonly class VoucherNotificationValidator
{
    public function __construct(private ?ClockInterface $clock = null)
    {
    }

    public function assertCanNotify(User $user, Voucher $voucher): void
    {
        $now = $this->clock?->now() ?? new \DateTimeImmutable();

        if (!$voucher->isActive()) {
            throw new \DomainException('Impossible de notifier un voucher inactif.');
        }

        if (!$voucher->hasStartedAt($now)) {
            throw new \DomainException('Impossible de notifier un voucher qui n\'est pas encore disponible.');
        }

        if ($voucher->isExpiredAt($now)) {
            throw new \DomainException('Impossible de notifier un voucher expiré.');
        }

        if (!$voucher->matchesRecipient($user)) {
            throw new \DomainException('Impossible de notifier un voucher attribué à un autre destinataire.');
        }
    }
}
