<?php

declare(strict_types=1);

namespace App\Module\Rating\Exception;

use App\Shared\Http\ApiProblemException;

class ProductReviewException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 422;
    }
}
