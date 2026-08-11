<?php

declare(strict_types=1);

namespace App\Module\News\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateNewsCommentInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 2000)]
        public string $content,
    )
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['content'] ?? null) ? trim($payload['content']) : '');
    }
}
