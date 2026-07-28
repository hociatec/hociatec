<?php

declare(strict_types=1);

namespace App\Module\Admin\News\Controller;

use App\Module\News\Repository\NewsArticleRepository;
use App\Module\News\Service\NewsFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class GetAdminNewsArticleController
{
    public function __construct(private NewsArticleRepository $articles, private NewsFormatter $formatter)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $article = $this->articles->find($id);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(['article' => $this->formatter->article($article)]);
    }
}
