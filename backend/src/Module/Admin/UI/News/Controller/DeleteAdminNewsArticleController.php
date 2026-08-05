<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class DeleteAdminNewsArticleController
{
    public function __construct(private NewsArticleRepositoryPort $articles, private NewsArticleWriter $writer)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $article = $this->articles->find($id);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $this->writer->delete($article);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'Actualité supprimée.');
    }
}
