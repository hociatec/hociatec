<?php

declare(strict_types=1);

namespace App\Module\News\UI\Controller\PublicApi;

use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Infrastructure\Repository\NewsArticleRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/news', name: 'api_public_news_list', methods: ['GET'])]
#[RateLimited('public_api')]
final readonly class ListNewsArticlesController
{
    public function __construct(
        private NewsArticleRepository $articles,
        private NewsFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $search = trim((string) $request->query->get('q', ''));
        $pagination = Pagination::fromRequest($request, 9, 30);
        $total = $this->articles->countPublished($search);

        return ApiResponse::paginated(
            array_map(
                fn ($article): array => $this->formatter->article($article),
                $this->articles->findPublished($search, $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($total),
        );
    }
}
