<?php

declare(strict_types=1);

namespace App\Domain\Normalization;

final class EmailNormalizer
{
    private function __construct()
    {
    }

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
