<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Workflow\CustomerBugReportPortalService;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
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
        private readonly CustomerBugReportPortalService $portal,
        private readonly BugReportCommentFormatter $formatter,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $pagination = RequestQueryMapper::pagination($request, 6, 50);
        try {
            $result = $this->portal->listCommentsForUser($user, $id, $pagination->perPage, $pagination->offset());
        } catch (\DomainException $exception) {
            return ApiResponse::error('Accès refusé.', 403);
        }
        if (null === $result) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        return ApiResponse::paginated(
            array_map(fn (BugReportComment $comment): array => $this->formatter->format($comment), $result['items']),
            $pagination->metadata($result['total']),
        );
    }
}
