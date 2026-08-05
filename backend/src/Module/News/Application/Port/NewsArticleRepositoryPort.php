<?php

declare(strict_types=1);

namespace App\Module\News\Application\Port;

use App\Module\News\Domain\Entity\NewsArticle;
use Doctrine\DBAL\LockMode;

interface NewsArticleRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?NewsArticle;

    /** @return list<NewsArticle> */
    public function findPublished(?string $search, int $limit, int $offset): array;

    public function countPublished(?string $search): int;

    /** @return list<NewsArticle> */
    public function findForAdmin(?string $search, int $limit, int $offset): array;

    public function countForAdmin(?string $search): int;

    public function findPublishedBySlug(string $slug): ?NewsArticle;
}
