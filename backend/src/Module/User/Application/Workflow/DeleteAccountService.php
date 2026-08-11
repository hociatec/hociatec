<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\User\Application\Exception\DeleteAccountBlockedException;
use App\Module\User\Application\Port\UserPersistencePort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;

final readonly class DeleteAccountService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private RefreshTokenRevocationService $refreshTokenRevocations,
        private UserPersistencePort $persistence,
        private TransactionManager $transactions,
    ) {
    }

    public function delete(User $user): void
    {
        if ($this->orders->hasActiveForUser($user)) {
            throw DeleteAccountBlockedException::activeOrders();
        }

        $this->transactions->transactional(function () use ($user): void {
            $this->refreshTokenRevocations->revokeAllForUser($user);
            $this->persistence->remove($user);
            $this->persistence->flush();
        });
    }
}
