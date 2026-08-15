<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\DTO;

final readonly class RefreshTokenContext
{
    public function __construct(
        public ?string $deviceIdentifier,
        public ?string $deviceLabel,
        public ?string $platformLabel,
        public ?string $clientLabel,
        public ?string $locationLabel,
        public ?string $userAgent,
        public ?string $ipAddress,
    ) {
    }
}
