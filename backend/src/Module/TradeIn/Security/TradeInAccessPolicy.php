<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Security;

use App\Module\TradeIn\Entity\TradeInRequest;
use App\Module\User\Entity\User;

final readonly class TradeInAccessPolicy
{
    public function canDownloadReceipt(User $user, TradeInRequest $request): bool
    {
        return null !== $request->getUser()
            && $request->getUser()->getId() === $user->getId()
            && null !== $request->getReceiptPath();
    }
}
