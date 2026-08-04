<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Http\BugReportCommentFormatter;
use App\Module\BetaTest\Repository\BugReportCommentRepository;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\BetaTest\Security\BugReportAccessPolicy;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
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
        private readonly BugReportRepository $reports,
        private readonly BugReportCommentRepository $comments,
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

        if (!$this->accessPolicy->canView($user, $report)) {
            return ApiResponse::error('Accès refusé.', 403);
        }

        $pagination = Pagination::fromRequest($request, 6, 50);
        $commentsList = $this->comments->findForReportPaginated($report, $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($comment): array => $this->formatter->format($comment), $commentsList),
            $pagination->metadata($this->comments->countForReport($report)),
        );
    }
}
