<?php

declare(strict_types=1);

namespace App\Module\News\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class NewsArticleInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public string $slug,
        #[Assert\NotBlank]
        #[Assert\Length(max: 1000)]
        public string $excerpt,
        #[Assert\NotBlank]
        #[Assert\Length(max: 50000)]
        public string $content,
        #[Assert\Length(max: 120)]
        public ?string $category,
        public bool $isPublished,
        #[Assert\Length(max: 40)]
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
