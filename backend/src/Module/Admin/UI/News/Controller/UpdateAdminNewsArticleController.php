<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class UpdateAdminNewsArticleController
{
    public function __construct(private NewsArticleRepositoryPort $articles, private NewsArticleWriter $writer, private NewsFormatter $formatter)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $article = $this->articles->find($id);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $article = $this->writer->update($article, \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, NewsArticleInput::class));
        } catch (InvalidJsonPayloadException|\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (NewsOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success(['article' => $this->formatter->article($article)], JsonResponse::HTTP_OK, 'Actualité mise à jour.');
    }
}
