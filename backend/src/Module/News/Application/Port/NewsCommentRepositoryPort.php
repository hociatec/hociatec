<?php

declare(strict_types=1);

namespace App\Module\News\Application\Port;

use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsComment;
use App\Shared\Application\LockMode;

interface NewsCommentRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?NewsComment;

    /** @return list<NewsComment> */
    public function findForArticle(NewsArticle $article, int $limit, int $offset): array;

    public function countForArticle(NewsArticle $article): int;
}
