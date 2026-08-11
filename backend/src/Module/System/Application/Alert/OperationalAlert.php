<?php

declare(strict_types=1);

namespace App\Module\System\Application\Alert;

final readonly class OperationalAlert
{
    public function __construct(
        public string $severity,
        public string $message,
        public string $metric,
        public float|int $value,
    ) {
    }
}
