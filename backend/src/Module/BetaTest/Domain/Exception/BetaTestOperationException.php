<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Exception;

final class BetaTestOperationException extends \RuntimeException
{
    public static function failed(string $message, \Throwable $previous): self
    {
        return new self($message, 0, $previous);
    }
}
