<?php

declare(strict_types=1);

namespace App\Module\News\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;

final readonly class NewsArticleViewTracker
{
    public function __construct(
        private NewsArticleViewRepository $views,
        private DoctrineUnitOfWork $persistence,
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

        $this->persistence->commit();
    }
}
