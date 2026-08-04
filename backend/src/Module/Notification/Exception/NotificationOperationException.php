<?php

declare(strict_types=1);

namespace App\Module\Notification\Exception;

final class NotificationOperationException extends \RuntimeException
{
    public static function failed(string $message, \Throwable $previous): self
    {
        return new self($message, 0, $previous);
    }
}
