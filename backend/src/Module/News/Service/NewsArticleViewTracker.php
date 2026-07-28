<?php

declare(strict_types=1);

namespace App\Module\News\Service;

use App\Module\News\Entity\NewsArticle;
use App\Module\News\Entity\NewsArticleView;
use App\Module\News\Repository\NewsArticleViewRepository;
use App\Shared\Persistence\DoctrinePersistence;

final readonly class NewsArticleViewTracker
{
    public function __construct(
        private NewsArticleViewRepository $views,
        private DoctrinePersistence $persistence,
    ) {
    }

    public function track(NewsArticle $article, ?string $ip): void
    {
        $ip = trim((string) $ip);
        if ('' === $ip) {
            return;
        }

        $ipHash = hash('sha256', $ip);
        $view = $this->views->findOneForArticleAndIpHash($article, $ipHash);
        if (null === $view) {
            $this->persistence->persist(new NewsArticleView($article, $ipHash));
        } else {
            $view->markViewed();
        }

        $this->persistence->flush();
    }
}
