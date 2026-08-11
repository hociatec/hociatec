<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Exception;

use App\Shared\Application\Exception\AbstractPublicApiException;

final class ProductFormRequestException extends AbstractPublicApiException
{
    public function __construct(string $message, int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }
}
