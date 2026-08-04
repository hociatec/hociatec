<?php

declare(strict_types=1);

namespace App\Infrastructure\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ConstraintViolationFormatter
{
    /**
     * @param array<string, string> $propertyAliases
     *
     * @return list<string>
     */
    public function format(
        ConstraintViolationListInterface $violations,
        array $propertyAliases = [],
    ): array {
        $errors = [];
        foreach ($violations as $violation) {
            $property = (string) $violation->getPropertyPath();
            $errors[] = sprintf(
                '%s: %s',
                $propertyAliases[$property] ?? $property,
                $violation->getMessage(),
            );
        }

        return $errors;
    }
}
