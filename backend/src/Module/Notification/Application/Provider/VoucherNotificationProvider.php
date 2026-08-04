<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Provider;

use App\Module\Notification\Application\Notification\ComputedAccountNotificationProviderInterface;
use App\Module\Notification\Application\Projection\AccountNotificationFormatter;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;

final readonly class VoucherNotificationProvider implements ComputedAccountNotificationProviderInterface
{
    public function __construct(
        private VoucherRepository $vouchers,
        private AccountNotificationFormatter $formatter,
    ) {
    }

    public function provide(User $user, \DateTimeImmutable $now): array
    {
        $usableVouchers = $this->usableVouchers($user, $now);
        if ([] === $usableVouchers) {
            return [];
        }

        $voucherIds = array_map(static fn (Voucher $voucher): int => (int) $voucher->getId(), $usableVouchers);
        sort($voucherIds);
        $count = count($usableVouchers);

        return [
            $this->formatter->computedNotification(
                'vouchers:'.implode(',', $voucherIds),
                $count.' bon'.($count > 1 ? 's' : '').' disponible'.($count > 1 ? 's' : ''),
                'Vous avez '.$count.' bon'.($count > 1 ? 's' : '').' utilisable'.($count > 1 ? 's' : '').' sur votre compte.',
                '/vouchers/me',
                'voucher_available',
                $now,
            ),
        ];
    }

    /**
     * @return list<Voucher>
     */
    private function usableVouchers(User $user, \DateTimeImmutable $now): array
    {
        $userId = $user->getId();
        if (null === $userId) {
            return [];
        }

        return array_values(array_filter(
            $this->vouchers->findByRecipientUserId($userId),
            static fn (Voucher $voucher): bool => $voucher->isActive()
                && (null === $voucher->getStartsAt() || $voucher->getStartsAt() <= $now)
                && (null === $voucher->getEndsAt() || $voucher->getEndsAt() >= $now),
        ));
    }
}
