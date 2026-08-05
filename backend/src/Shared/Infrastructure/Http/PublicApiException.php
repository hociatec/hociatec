<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

interface PublicApiException extends ApiProblemException, \App\Shared\Application\Exception\PublicApiException
{
}
