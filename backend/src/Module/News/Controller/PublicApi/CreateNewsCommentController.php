<?php

declare(strict_types=1);

namespace App\Module\News\Controller\PublicApi;

use App\Module\News\DTO\CreateNewsCommentInput;
use App\Module\News\Exception\NewsOperationException;
use App\Module\News\Repository\NewsArticleRepository;
use App\Module\News\Service\NewsCommentWriter;
use App\Module\News\Service\NewsFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/public/news/{slug}/comments', name: 'api_public_news_comments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimited('public_api')]
final class CreateNewsCommentController extends AbstractController
{
    public function __construct(
        private readonly NewsArticleRepository $articles,
        private readonly NewsCommentWriter $writer,
        private readonly NewsFormatter $formatter,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $article = $this->articles->findPublishedBySlug($slug);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $input = CreateNewsCommentInput::fromArray(JsonPayload::decode($request));
        try {
            $comment = $this->writer->create($article, $user, $input->content);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NewsOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::created(['comment' => $this->formatter->comment($comment)], 'Commentaire publié.');
    }
}
