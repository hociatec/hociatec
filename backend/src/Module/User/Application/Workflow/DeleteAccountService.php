<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\User\Application\Exception\DeleteAccountBlockedException;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteAccountService
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private RefreshTokenRevocationService $refreshTokenRevocations,
        private UserPersonalDataAnonymizer $anonymizer,
        private UnitOfWork $persistence,
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

            if ($this->anonymizer->shouldRetainHistory($user)) {
                $this->anonymizer->anonymize($user);

                return;
            }

            $this->persistence->remove($user);
        });
    }
}
