<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/{id}/send-email', methods: ['POST'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_NEWS_MANAGER')]
final readonly class SendAdminNewsArticleEmailController
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

        try {
            $this->writer->sendPublishedEmails($article);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Envoi de l’actualité impossible.', JsonResponse::HTTP_BAD_REQUEST);
        } catch (NewsOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::success(['sent' => true], JsonResponse::HTTP_OK, 'Envoi des e-mails d’actualité planifié.');
    }
}
