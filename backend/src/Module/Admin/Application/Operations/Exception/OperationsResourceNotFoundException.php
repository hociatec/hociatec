<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class OperationsResourceNotFoundException extends AbstractApiProblemException
{
    public function __construct(string $message = 'Ressource introuvable.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $message, 'RESOURCE_NOT_FOUND', previous: $previous);
    }
}
