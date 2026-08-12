<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

class ProductReviewException extends AbstractApiProblemException
{
    public function __construct(string $message, int $statusCode = 422, ?string $errorCode = null)
    {
        parent::__construct($message, $statusCode, $message, $errorCode);
    }
}
