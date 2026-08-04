<?php

declare(strict_types=1);

namespace App\Module\News\Application\Message;

final readonly class NewsArticlePublishedEmailMessage
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $excerpt,
        public string $slug,
    ) {
    }
}
