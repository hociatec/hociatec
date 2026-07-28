<?php

declare(strict_types=1);

namespace App\Module\Admin\News\Controller;

use App\Module\News\Repository\NewsCommentRepository;
use App\Module\News\Service\NewsArticleWriter;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/comments/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class DeleteAdminNewsCommentController
{
    public function __construct(private NewsCommentRepository $comments, private NewsArticleWriter $writer)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $comment = $this->comments->find($id);
        if (null === $comment) {
            return ApiResponse::error('Commentaire introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $this->writer->delete($comment);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'Commentaire supprimé.');
    }
}
