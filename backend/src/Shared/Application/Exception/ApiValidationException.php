<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class ApiValidationException extends AbstractPublicApiException
{
    /** @var list<string> */
    public readonly array $details;
    public readonly int $statusCode;

    /**
     * @param list<string> $details
     */
    public function __construct(
        string $message,
        array $details,
        int $statusCode = 422,
    ) {
        $this->details = $details;
        $this->statusCode = $statusCode;

        parent::__construct(
            $message,
            $statusCode,
            errorCode: 'VALIDATION_ERROR',
            details: $details,
        );
    }
}
