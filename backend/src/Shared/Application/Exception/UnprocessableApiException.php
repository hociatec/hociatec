<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class UnprocessableApiException extends AbstractPublicApiException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}
