<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class CartAlreadyConvertedException extends AbstractApiProblemException
{
    public function __construct(string $message = 'Cette commande a déjà été validée.')
    {
        parent::__construct($message, 409, $message, 'CART_ALREADY_CONVERTED');
    }
}
