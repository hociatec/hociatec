<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\News\Controller;

use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news', methods: ['POST'])]
#[IsGranted('ROLE_NEWS_MANAGER')]
final readonly class CreateAdminNewsArticleController
{
    public function __construct(private NewsArticleWriter $writer, private NewsFormatter $formatter, private DtoValidator $validator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, NewsArticleInput::class);
            $this->validator->validate($input);
            $article = $this->writer->create($input);
        } catch (ApiValidationException|InvalidJsonPayloadException|\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (NewsOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::createdItem('article', $this->formatter->article($article), 'Actualité créée.');
    }
}
