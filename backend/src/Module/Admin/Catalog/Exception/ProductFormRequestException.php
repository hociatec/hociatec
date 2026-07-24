<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Exception;

use App\Shared\Http\ApiProblemException;

final class ProductFormRequestException extends \RuntimeException implements ApiProblemException
{
    public function __construct(string $message, private readonly int $statusCode)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
