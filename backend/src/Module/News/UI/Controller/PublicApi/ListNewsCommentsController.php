<?php

declare(strict_types=1);

namespace App\Module\News\UI\Controller\PublicApi;

use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\News\Application\Port\NewsCommentRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/news/{slug}/comments', name: 'api_public_news_comments_list', methods: ['GET'])]
#[RateLimited('public_api')]
final readonly class ListNewsCommentsController
{
    public function __construct(
        private NewsArticleRepositoryPort $articles,
        private NewsCommentRepositoryPort $comments,
        private NewsFormatter $formatter,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $article = $this->articles->findPublishedBySlug($slug);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $pagination = RequestQueryMapper::pagination($request, 10, 30);
        $total = $this->comments->countForArticle($article);

        return ApiResponse::paginated(
            array_map(
                fn ($comment): array => $this->formatter->comment($comment),
                $this->comments->findForArticle($article, $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($total),
        );
    }
}
