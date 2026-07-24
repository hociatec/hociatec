<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Exception;

use App\Shared\Http\ApiProblemException;

final class OperationsResourceNotFoundException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 404;
    }
}
