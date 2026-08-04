<?php

declare(strict_types=1);

namespace App\Module\News\Application\DTO;

final readonly class CreateNewsCommentInput
{
    public function __construct(public string $content)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['content'] ?? null) ? trim($payload['content']) : '');
    }
}
