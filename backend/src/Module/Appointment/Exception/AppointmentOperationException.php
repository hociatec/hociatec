<?php

declare(strict_types=1);

namespace App\Module\Appointment\Exception;

final class AppointmentOperationException extends \RuntimeException
{
    public static function failed(string $message, \RuntimeException $previous): self
    {
        return new self($message, 0, $previous);
    }
}
