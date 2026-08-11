<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class BadRequestApiException extends AbstractPublicApiException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
