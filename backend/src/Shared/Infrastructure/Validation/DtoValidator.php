<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validation;

use App\Shared\Infrastructure\Http\ApiValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class DtoValidator
{
    public function __construct(
        private ValidatorInterface $validator,
        private ConstraintViolationFormatter $formatter,
    ) {
    }

    /**
     * @param array<string, string> $propertyAliases
     */
    public function validate(
        object $dto,
        array $propertyAliases = [],
        string $message = 'Validation des donnees echouee.',
        int $statusCode = JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
    ): void {
        $violations = $this->validator->validate($dto);
        if (0 === $violations->count()) {
            return;
        }

        throw new ApiValidationException($message, $this->formatter->format($violations, $propertyAliases), $statusCode);
    }
}
