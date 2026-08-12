<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class TrainingSessionUnavailableException extends AbstractApiProblemException
{
    public function __construct(string $message = 'Session indisponible.')
    {
        parent::__construct($message, 409, $message, 'TRAINING_SESSION_UNAVAILABLE');
    }
}
