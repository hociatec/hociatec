<?php

declare(strict_types=1);

namespace App\Module\News\Application\Projection;

use App\Module\News\Application\Port\NewsArticleViewRepositoryPort;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsComment;

final readonly class NewsFormatter
{
    public function __construct(private NewsArticleViewRepositoryPort $views)
    {
    }

    /** @return array<string, mixed> */
    public function article(NewsArticle $article): array
    {
        return [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'excerpt' => $article->getExcerpt(),
            'content' => $article->getContent(),
            'category' => $article->getCategory(),
            'isPublished' => $article->isPublished(),
            'viewsCount' => $this->views->countUniqueForArticle($article),
            'publishedAt' => $article->getPublishedAt()?->format(\DateTimeImmutable::ATOM),
            'createdAt' => $article->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'updatedAt' => $article->getUpdatedAt()->format(\DateTimeImmutable::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function comment(NewsComment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'author' => [
                'id' => $comment->getAuthor()->getId(),
                'name' => trim($comment->getAuthor()->getFirstName().' '.$comment->getAuthor()->getLastName()),
            ],
        ];
    }
}
