<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

final class E2eCommandGuard
{
    public static function isAllowed(): bool
    {
        $environment = self::environment();
        if ('prod' === $environment) {
            return false;
        }

        if ('e2e' === $environment) {
            return true;
        }

        return self::flagEnabled();
    }

    public static function denialMessage(string $commandName): string
    {
        $environment = self::environment();
        if ('prod' === $environment) {
            return sprintf('The %s command is blocked in the prod environment.', $commandName);
        }

        return sprintf(
            'The %s command is blocked unless APP_E2E=1 or APP_ENV=e2e is set.',
            $commandName,
        );
    }

    private static function environment(): ?string
    {
        $value = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: null;

        return is_string($value) ? strtolower(trim($value)) : null;
    }

    private static function flagEnabled(): bool
    {
        $value = $_SERVER['APP_E2E'] ?? $_ENV['APP_E2E'] ?? getenv('APP_E2E') ?: null;
        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }
}
