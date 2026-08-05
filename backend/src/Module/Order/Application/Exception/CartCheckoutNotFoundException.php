<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Exception;

use App\Shared\Application\Exception\ApiProblemException;

final class CartCheckoutNotFoundException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 404;
    }
}
