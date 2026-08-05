<?php

declare(strict_types=1);

namespace App\Module\News\UI\Controller\PublicApi;

use App\Module\News\Application\DTO\ShareNewsArticleInput;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\News\Application\Workflow\NewsArticleShareEmailService;
use App\Shared\Application\Exception\MailDeliveryException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/news/{slug}/share', name: 'api_public_news_share', methods: ['POST'])]
#[RateLimited('content_share_public')]
final readonly class ShareNewsArticleEmailController
{
    public function __construct(
        private NewsArticleRepositoryPort $articles,
        private NewsArticleShareEmailService $sharing,
        private DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $article = $this->articles->findPublishedBySlug($slug);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $input = ShareNewsArticleInput::fromPayload(\App\Shared\Infrastructure\Http\JsonRequestInput::payload($request));
        $this->dtoValidator->validate($input);

        try {
            $this->sharing->send($article, $input->email);
        } catch (MailDeliveryException) {
            return ApiResponse::error(
                "Impossible d'envoyer le message pour le moment.",
                JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return ApiResponse::success([
            'sent' => true,
            'to' => $input->email,
            'message' => 'L’actualité a été envoyée par e-mail.',
        ]);
    }
}
