<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Exception;

use App\Shared\Application\Exception\AbstractPublicApiException;
use App\Shared\Application\Exception\ApiProblemException;

final class ProductFormRequestException extends AbstractPublicApiException
{
    public function __construct(string $message, int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }

    public static function fromInvalidArgument(\InvalidArgumentException $exception, int $statusCode): self
    {
        return new self(
            $exception instanceof ApiProblemException ? $exception->publicMessage() : 'Requête produit invalide.',
            $statusCode,
        );
    }
}
