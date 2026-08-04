<?php

declare(strict_types=1);

namespace App\Module\Loyalty\Exception;

final class LoyaltyOperationException extends \RuntimeException
{
    public static function failed(string $message, \Throwable $previous): self
    {
        return new self($message, 0, $previous);
    }
}
