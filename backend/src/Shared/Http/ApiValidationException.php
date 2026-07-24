<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\Response;

final class ApiValidationException extends \RuntimeException
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        string $message,
        public readonly array $details,
        public readonly int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
    ) {
        parent::__construct($message);
    }
}
