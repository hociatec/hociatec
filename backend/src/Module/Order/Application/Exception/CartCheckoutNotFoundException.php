<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Exception;

use App\Shared\Infrastructure\Http\ApiProblemException;

final class CartCheckoutNotFoundException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 404;
    }
}
