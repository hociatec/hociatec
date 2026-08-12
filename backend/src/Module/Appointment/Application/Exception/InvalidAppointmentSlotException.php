<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class InvalidAppointmentSlotException extends AbstractApiProblemException
{
    public function __construct(string $message = 'Ce créneau n\'est plus disponible.')
    {
        parent::__construct($message, 422, $message, 'INVALID_APPOINTMENT_SLOT');
    }
}
