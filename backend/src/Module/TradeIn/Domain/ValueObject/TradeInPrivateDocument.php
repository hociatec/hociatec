<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInPrivateDocument
{
    public function __construct(
        public string $path,
        public ?string $originalName = null,
        public ?int $size = null,
        public ?string $sha256 = null,
    ) {
    }
}
