<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Exception;

use App\Shared\Infrastructure\Http\ApiProblemException;

final class TrainingSessionUnavailableException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 409;
    }
}
