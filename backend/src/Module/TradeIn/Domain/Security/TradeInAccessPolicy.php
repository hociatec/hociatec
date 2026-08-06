<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Security;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\User\Domain\Entity\User;

final readonly class TradeInAccessPolicy
{
    public function canRespondToOffer(User $user, TradeInRequest $request): bool
    {
        return $this->isOwner($user, $request);
    }

    public function canDownloadReceipt(User $user, TradeInRequest $request): bool
    {
        return $this->isOwner($user, $request)
            && null !== $request->getReceiptPath();
    }

    private function isOwner(User $user, TradeInRequest $request): bool
    {
        $userId = $user->getId();
        $ownerId = $request->getUserId();
        if (null !== $userId && null !== $ownerId) {
            return $ownerId === $userId;
        }

        return $request->getUser() === $user;
    }
}
