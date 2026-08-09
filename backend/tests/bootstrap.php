<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (class_exists(\App\Shared\Infrastructure\DateTime\DateTimeParser::class) && !class_exists(\App\Shared\Domain\DateTime\DateTimeParser::class)) {
    class_alias(\App\Shared\Infrastructure\DateTime\DateTimeParser::class, \App\Shared\Domain\DateTime\DateTimeParser::class);
}

if (class_exists(\App\Shared\Application\Exception\ApiValidationException::class) && !class_exists(\App\Shared\Infrastructure\Http\ApiValidationException::class)) {
    class_alias(\App\Shared\Application\Exception\ApiValidationException::class, \App\Shared\Infrastructure\Http\ApiValidationException::class);
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
