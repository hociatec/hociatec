<?php

declare(strict_types=1);

namespace App\Module\News\UI\Controller\PublicApi;

use App\Module\News\Application\DTO\CreateNewsCommentInput;
use App\Module\News\Application\Port\NewsArticleRepositoryPort;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\News\Application\Writer\NewsCommentWriter;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly NewsArticleRepositoryPort $articles,
        private readonly NewsCommentWriter $writer,
        private readonly NewsFormatter $formatter,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $article = $this->articles->findPublishedBySlug($slug);
        if (null === $article) {
            return ApiResponse::error('Actualité introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, CreateNewsCommentInput::class);
            $this->dtoValidator->validate($input, message: 'Commentaire invalide.');
            $comment = $this->writer->create($article, $user, $input->content);
        } catch (ApiValidationException $exception) {
            return ApiProblemResponse::fromThrowable($exception);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Commentaire invalide.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NewsOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::createdItem('comment', $this->formatter->comment($comment), 'Commentaire publié.');
    }
}
