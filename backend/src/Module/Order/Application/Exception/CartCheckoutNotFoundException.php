<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class CartCheckoutNotFoundException extends AbstractApiProblemException
{
    public function __construct(string $message = 'Ressource de checkout introuvable.', ?string $errorCode = null)
    {
        parent::__construct($message, 404, $message, $errorCode ?? 'CHECKOUT_NOT_FOUND');
    }
}
