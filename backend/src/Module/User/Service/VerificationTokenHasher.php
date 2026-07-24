<?php

declare(strict_types=1);

namespace App\Module\User\Service;

final class VerificationTokenHasher
{
    public const RAW_TOKEN_BYTES = 32;
    public const HASH_ALGORITHM = 'sha256';

    private function __construct()
    {
    }

    public static function generateRawToken(): string
    {
        return bin2hex(random_bytes(self::RAW_TOKEN_BYTES));
    }

    public static function hash(string $rawToken): string
    {
        return hash(self::HASH_ALGORITHM, $rawToken);
    }

    public static function isValidRawToken(string $rawToken): bool
    {
        return strlen($rawToken) === self::RAW_TOKEN_BYTES * 2 && ctype_xdigit($rawToken);
    }
}
