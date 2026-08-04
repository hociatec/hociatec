<?php

declare(strict_types=1);

namespace App\Module\News\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Infrastructure\Http\RateLimited;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Infrastructure\Repository\NewsArticleRepository;
use App\Module\News\Infrastructure\Repository\NewsCommentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/news/{slug}/comments', name: 'api_public_news_comments_list', methods: ['GET'])]
#[RateLimited('public_api')]
final readonly class ListNewsCommentsController
{
    public function __construct(
        private NewsArticleRepository $articles,
        private NewsCommentRepository $comments,
        private NewsFormatter $formatter,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $article = $this->articles->findPublishedBySlug($slug);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $pagination = Pagination::fromRequest($request, 10, 30);
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
