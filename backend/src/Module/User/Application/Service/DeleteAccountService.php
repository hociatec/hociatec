<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\Auth\Infrastructure\Repository\RefreshTokenRepository;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\User\Application\Exception\DeleteAccountBlockedException;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;

final readonly class DeleteAccountService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private RefreshTokenRepository $refreshTokens,
        private UserPersistence $persistence,
        private TransactionManager $transactions,
    ) {
    }

    public function delete(User $user): void
    {
        if ($this->orders->hasActiveForUser($user)) {
            throw DeleteAccountBlockedException::activeOrders();
        }

        $this->transactions->transactional(function () use ($user): void {
            $this->refreshTokens->revokeAllForUser($user);
            $this->persistence->remove($user);
            $this->persistence->commit();
        });
    }
}
