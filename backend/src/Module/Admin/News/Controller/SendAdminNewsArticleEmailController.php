<?php

declare(strict_types=1);

namespace App\Module\Admin\News\Controller;

use App\Module\News\Repository\NewsArticleRepository;
use App\Module\News\Service\NewsArticleWriter;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/{id}/send-email', methods: ['POST'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class SendAdminNewsArticleEmailController
{
    public function __construct(private NewsArticleRepository $articles, private NewsArticleWriter $writer)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $article = $this->articles->find($id);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $this->writer->sendPublishedEmails($article);
        } catch (\Exception $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['sent' => true], JsonResponse::HTTP_OK, 'Envoi des e-mails d’actualité planifié.');
    }
}
