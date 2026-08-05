<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Exception;

use App\Shared\Application\Exception\ApiProblemException;

class ProductReviewException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 422;
    }
}
