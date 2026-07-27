<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Repository\BugReportCommentRepository;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}/comments', name: 'api_beta_reports_comments_list', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListBugReportCommentsController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly BugReportCommentRepository $comments,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        if (!$isAdmin && $report->getReporter()->getId() !== $user->getId()) {
            return ApiResponse::error('Accès refusé.', 403);
        }

        $commentsList = $this->comments->findBy(['bugReport' => $report], ['createdAt' => 'ASC']);

        return ApiResponse::success([
            'items' => array_map(static fn ($c) => [
                'id' => $c->getId(),
                'content' => $c->getContent(),
                'createdAt' => $c->getCreatedAt()->format(DATE_ATOM),
                'author' => [
                    'id' => $c->getAuthor()->getId(),
                    'firstName' => $c->getAuthor()->getFirstName(),
                    'lastName' => $c->getAuthor()->getLastName(),
                    'email' => $c->getAuthor()->getEmail(),
                    'role' => in_array('ROLE_ADMIN', $c->getAuthor()->getRoles(), true) ? 'admin' : 'user',
                ],
            ], $commentsList),
        ]);
    }
}
