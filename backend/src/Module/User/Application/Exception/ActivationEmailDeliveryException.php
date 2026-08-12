<?php

declare(strict_types=1);

namespace App\Module\User\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class ActivationEmailDeliveryException extends AbstractApiProblemException
{
    public static function deliveryFailed(\Throwable $previous): self
    {
        return new self(
            "L'e-mail d'activation n'a pas pu etre envoye.",
            previous: $previous,
        );
    }

    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 503, 'Le service d’activation est momentanément indisponible.', 'ACTIVATION_EMAIL_DELIVERY_FAILED', previous: $previous);
    }
}
