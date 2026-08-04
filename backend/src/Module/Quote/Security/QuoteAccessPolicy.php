<?php

declare(strict_types=1);

namespace App\Module\Quote\Security;

use App\Module\Quote\Entity\Quote;
use App\Module\User\Entity\User;

final readonly class QuoteAccessPolicy
{
    public function canView(User $user, Quote $quote): bool
    {
        return strtolower((string) $quote->getCustomerEmail()) === strtolower($user->getEmail());
    }
}
