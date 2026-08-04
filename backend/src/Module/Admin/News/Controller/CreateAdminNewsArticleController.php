<?php

declare(strict_types=1);

namespace App\Module\Admin\News\Controller;

use App\Module\News\DTO\NewsArticleInput;
use App\Module\News\Exception\NewsOperationException;
use App\Module\News\Service\NewsArticleWriter;
use App\Module\News\Service\NewsFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\InvalidJsonPayloadException;
use App\Shared\Http\JsonPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/news', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class CreateAdminNewsArticleController
{
    public function __construct(private NewsArticleWriter $writer, private NewsFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $article = $this->writer->create(NewsArticleInput::fromArray(JsonPayload::decode($request)));
        } catch (InvalidJsonPayloadException|\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (NewsOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::created(['article' => $this->formatter->article($article)], 'Actualité créée.');
    }
}
