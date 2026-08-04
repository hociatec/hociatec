<?php

declare(strict_types=1);

namespace App\Module\News\UI\Controller\PublicApi;

use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Workflow\NewsArticleViewTracker;
use App\Module\News\Infrastructure\Repository\NewsArticleRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/news/{slug}', name: 'api_public_news_show', methods: ['GET'])]
#[RateLimited('public_api')]
final readonly class ShowNewsArticleController
{
    public function __construct(
        private NewsArticleRepository $articles,
        private NewsFormatter $formatter,
        private NewsArticleViewTracker $views,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $article = $this->articles->findPublishedBySlug($slug);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $this->views->track($article, $request->getClientIp());

        return ApiResponse::successItem('article', $this->formatter->article($article));
    }
}
