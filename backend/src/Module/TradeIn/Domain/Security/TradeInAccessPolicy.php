<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Security;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\User\Domain\Entity\User;

final readonly class TradeInAccessPolicy
{
    public function canDownloadReceipt(User $user, TradeInRequest $request): bool
    {
        return null !== $request->getUser()
            && $request->getUser()->getId() === $user->getId()
            && null !== $request->getReceiptPath();
    }
}
