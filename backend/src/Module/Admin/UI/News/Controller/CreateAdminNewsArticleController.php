<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news', methods: ['POST'])]
#[IsGranted('ROLE_NEWS_MANAGER')]
final readonly class CreateAdminNewsArticleController
{
    public function __construct(private NewsArticleWriter $writer, private NewsFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $article = $this->writer->create(\App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, NewsArticleInput::class));
        } catch (InvalidJsonPayloadException|\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (NewsOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::createdItem('article', $this->formatter->article($article), 'Actualité créée.');
    }
}
