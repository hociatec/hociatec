<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

interface PublicApiException extends ApiProblemException
{
    public function publicMessage(): string;
}
