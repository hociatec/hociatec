<?php

declare(strict_types=1);

namespace App\Module\Auth\Domain\ValueObject;

final readonly class RefreshTokenAccessContext
{
    public function __construct(
        public ?string $deviceIdentifier = null,
        public ?string $deviceLabel = null,
        public ?string $platformLabel = null,
        public ?string $clientLabel = null,
        public ?string $locationLabel = null,
        public ?string $userAgent = null,
        public ?string $ipAddress = null,
    ) {
    }
}
