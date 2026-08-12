<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class CartCheckoutConflictException extends AbstractApiProblemException
{
    public function __construct(string $message = 'Conflit de checkout.', ?string $errorCode = null)
    {
        parent::__construct($message, 409, $message, $errorCode ?? 'CHECKOUT_CONFLICT');
    }
}
