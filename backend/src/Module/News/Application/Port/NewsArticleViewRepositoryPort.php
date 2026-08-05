<?php

declare(strict_types=1);

namespace App\Module\News\Application\Port;

use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;

interface NewsArticleViewRepositoryPort
{
    public function findOneForArticleAndIpHash(NewsArticle $article, string $ipHash): ?NewsArticleView;

    public function countUniqueForArticle(NewsArticle $article): int;
}
