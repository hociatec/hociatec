<?php

declare(strict_types=1);

namespace App\Module\User\Application\Exception;

use App\Shared\Infrastructure\Http\ApiProblemException;

final class ActivationEmailDeliveryException extends \RuntimeException implements ApiProblemException
{
    public static function deliveryFailed(\Throwable $previous): self
    {
        return new self(
            "L'e-mail d'activation n'a pas pu etre envoye.",
            previous: $previous,
        );
    }

    public function getStatusCode(): int
    {
        return 503;
    }
}
