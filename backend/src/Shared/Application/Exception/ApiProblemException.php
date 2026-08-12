<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

interface ApiProblemException
{
    public function getStatusCode(): int;

    public function publicMessage(): string;

    public function errorCode(): string;

    /**
     * @return array<string, mixed>|list<string>
     */
    public function details(): array;
}
