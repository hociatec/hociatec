<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Shared\Domain\ValueObject\Currency;

/**
 * @deprecated use App\Shared\Domain\ValueObject\Currency
 */
enum CheckoutCurrency: string
{
    case EUR = 'EUR';
    case USD = 'USD';

    public static function fromCode(string $code): Currency
    {
        return Currency::fromCode($code);
    }
}
