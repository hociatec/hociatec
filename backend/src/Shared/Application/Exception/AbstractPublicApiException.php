<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

abstract class AbstractPublicApiException extends AbstractApiProblemException implements PublicApiException
{
}
