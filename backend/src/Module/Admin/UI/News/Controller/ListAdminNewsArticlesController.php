<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class ListAdminNewsArticlesController
{
    public function __construct(private NewsArticleRepositoryPort $articles, private NewsFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request, 20, 50);
        $search = trim((string) $request->query->get('q', ''));

        return ApiResponse::paginated(
            array_map(fn ($article): array => $this->formatter->article($article), $this->articles->findForAdmin($search, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($this->articles->countForAdmin($search)),
        );
    }
}
