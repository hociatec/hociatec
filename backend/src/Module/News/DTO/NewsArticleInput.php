<?php

declare(strict_types=1);

namespace App\Module\News\DTO;

final readonly class NewsArticleInput
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $content,
        public ?string $category,
        public bool $isPublished,
        public ?string $publishedAt,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['title'] ?? null) ? trim($payload['title']) : '',
            is_string($payload['slug'] ?? null) ? trim($payload['slug']) : '',
            is_string($payload['excerpt'] ?? null) ? trim($payload['excerpt']) : '',
            is_string($payload['content'] ?? null) ? trim($payload['content']) : '',
            is_string($payload['category'] ?? null) ? trim($payload['category']) : null,
            is_bool($payload['isPublished'] ?? null) ? $payload['isPublished'] : true,
            is_string($payload['publishedAt'] ?? null) ? trim($payload['publishedAt']) : null,
        );
    }
}
