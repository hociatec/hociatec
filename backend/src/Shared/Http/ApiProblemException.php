<?php

declare(strict_types=1);

namespace App\Shared\Http;

interface ApiProblemException
{
    public function getStatusCode(): int;
}
