<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

final class MailDeliveryException extends \RuntimeException
{
    public static function failed(string $context, \Throwable $previous): self
    {
        return new self(sprintf('Email delivery failed for %s.', $context), previous: $previous);
    }
}
