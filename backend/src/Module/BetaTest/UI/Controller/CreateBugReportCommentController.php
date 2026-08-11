<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Workflow\CustomerBugReportPortalService;
use App\Module\BetaTest\Domain\Exception\BetaTestOperationException;
use App\Module\BetaTest\UI\Http\BugReportCommentFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}/comments', name: 'api_beta_reports_comments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimited('beta_report_comment')]
final class CreateBugReportCommentController extends AbstractController
{
    public function __construct(
        private readonly CustomerBugReportPortalService $portal,
        private readonly BugReportCommentFormatter $formatter,
    )
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);

        try {
            $comment = $this->portal->createCommentForUser($user, $id, (string) ($payload['content'] ?? ''));
        } catch (\DomainException $exception) {
            return ApiResponse::error('Accès refusé.', 403);
        } catch (\InvalidArgumentException $exception) {
            if ('' === trim((string) ($payload['content'] ?? ''))) {
                return ApiResponse::error('Le contenu du message ne peut pas être vide.', 422);
            }

            return ApiResponse::error('Rapport introuvable.', 404);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error('Le contenu du message ne peut pas être vide.', 422);
        } catch (BetaTestOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        if (null === $comment) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        return ApiResponse::created($this->formatter->format($comment), 'Message envoyé.');
    }
}
