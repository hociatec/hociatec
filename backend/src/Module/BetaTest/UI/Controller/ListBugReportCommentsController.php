<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Port\BugReportCommentRepositoryPort;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\BetaTest\UI\Http\BugReportCommentFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}/comments', name: 'api_beta_reports_comments_list', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListBugReportCommentsController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly BugReportCommentRepositoryPort $comments,
        private readonly BugReportAccessPolicy $accessPolicy,
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

        if (!$user->isAdmin() && !$this->accessPolicy->canView($user, $report)) {
            return ApiResponse::error('Accès refusé.', 403);
        }

        $pagination = RequestQueryMapper::pagination($request, 6, 50);
        $commentsList = $this->comments->findForReportPaginated($report, $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($comment): array => $this->formatter->format($comment), $commentsList),
            $pagination->metadata($this->comments->countForReport($report)),
        );
    }
}
