<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Exception;

use App\Infrastructure\Http\ApiProblemException;

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
