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
        $owner = $request->getUser();
        if (null === $owner) {
            return false;
        }

        $userId = $user->getId();
        $ownerId = $owner->getId();
        $sameUser = null !== $userId && null !== $ownerId ? $ownerId === $userId : $owner === $user;

        return $sameUser;
    }
}
