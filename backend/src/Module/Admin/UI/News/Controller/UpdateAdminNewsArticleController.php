<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_NEWS_MANAGER')]
final readonly class UpdateAdminNewsArticleController
{
    public function __construct(private NewsArticleRepositoryPort $articles, private NewsArticleWriter $writer, private NewsFormatter $formatter, private DtoValidator $validator)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $article = $this->articles->find($id);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, NewsArticleInput::class);
            $this->validator->validate($input);
            $article = $this->writer->update($article, $input);
        } catch (ApiValidationException|InvalidJsonPayloadException|\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Mise à jour de l’actualité invalide.', JsonResponse::HTTP_BAD_REQUEST);
        } catch (NewsOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::success(['article' => $this->formatter->article($article)], JsonResponse::HTTP_OK, 'Actualité mise à jour.');
    }
}
