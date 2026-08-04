<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Exception\BetaTestOperationException;
use App\Module\BetaTest\Http\BugReportCommentFormatter;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\BetaTest\Security\BugReportAccessPolicy;
use App\Module\BetaTest\Service\BugReportCommentWriter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Http\RateLimited;
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
        private readonly BugReportRepository $reports,
        private readonly BugReportAccessPolicy $accessPolicy,
        private readonly BugReportCommentWriter $writer,
        private readonly BugReportCommentFormatter $formatter,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        if (!$this->accessPolicy->canComment($user, $report)) {
            return ApiResponse::error('Accès refusé.', 403);
        }

        $payload = JsonPayload::decode($request);

        try {
            $comment = $this->writer->create($report, $user, (string) ($payload['content'] ?? ''));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error('Le contenu du message ne peut pas être vide.', 422);
        } catch (BetaTestOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::created($this->formatter->format($comment), 'Message envoyé.');
    }
}
