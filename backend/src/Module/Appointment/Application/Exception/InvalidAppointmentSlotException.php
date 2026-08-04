<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Exception;

use App\Infrastructure\Http\ApiProblemException;

final class InvalidAppointmentSlotException extends \RuntimeException implements ApiProblemException
{
    public function getStatusCode(): int
    {
        return 422;
    }
}
