<?php

declare(strict_types=1);

namespace App\Shared\Http;

interface PublicApiException extends ApiProblemException
{
    public function publicMessage(): string;
}
