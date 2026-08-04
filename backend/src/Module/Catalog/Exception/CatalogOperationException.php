<?php

declare(strict_types=1);

namespace App\Module\Catalog\Exception;

final class CatalogOperationException extends \RuntimeException
{
    public static function invalidOperation(string $message): self
    {
        return new self($message);
    }

    public static function failed(string $message, \RuntimeException $previous): self
    {
        return new self($message, 0, $previous);
    }
}
