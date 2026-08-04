<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class UserUniqueConstraintViolationDetector
{
    private const EMAIL_CONSTRAINT_NAMES = [
        'uniq_users_email',
        'uniq_1483a5e9e7927c74',
    ];

    private function __construct()
    {
    }

    public static function isEmail(UniqueConstraintViolationException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        foreach (self::EMAIL_CONSTRAINT_NAMES as $constraintName) {
            if (str_contains($message, $constraintName)) {
                return true;
            }
        }

        return str_contains($message, 'users.email')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'email'));
    }
}
