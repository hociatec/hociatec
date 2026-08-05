<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\AssignBugReportHandler;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/assignment', name: 'api_admin_beta_reports_assignment', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class AssignBugReportController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly UserRepositoryPort $users,
        private readonly AssignBugReportHandler $assignBugReport,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $assignedToId = isset($payload['assignedToId']) && '' !== (string) $payload['assignedToId'] ? (int) $payload['assignedToId'] : null;
        $assignedTo = null;
        if (null !== $assignedToId) {
            $assignedTo = $this->users->find($assignedToId);
            if (!$assignedTo instanceof User) {
                return ApiResponse::error('Administrateur introuvable.', 404);
            }
        }

        $actor = $this->getUser();
        try {
            $this->assignBugReport->assign($report, $assignedTo, $actor instanceof User ? $actor : null);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }

        return ApiResponse::success(['id' => $report->getId()], 200, 'Responsable mis à jour.');
    }
}
