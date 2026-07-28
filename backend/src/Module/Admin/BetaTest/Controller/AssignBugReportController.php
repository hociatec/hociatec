<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\BetaTest\Service\BugReportActivityLogger;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
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
        private readonly BugReportRepository $reports,
        private readonly UserRepository $users,
        private readonly DoctrinePersistence $persistence,
        private readonly BugReportActivityLogger $activityLogger,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $payload = JsonPayload::decode($request);
        $assignedToId = isset($payload['assignedToId']) && '' !== (string) $payload['assignedToId'] ? (int) $payload['assignedToId'] : null;
        $assignedTo = null;
        if (null !== $assignedToId) {
            $assignedTo = $this->users->find($assignedToId);
            if (!$assignedTo instanceof User || !in_array('ROLE_ADMIN', $assignedTo->getRoles(), true)) {
                return ApiResponse::error('Administrateur introuvable.', 404);
            }
        }

        $previous = $report->getAssignedTo()?->getEmail();
        $report->assignTo($assignedTo);
        $actor = $this->getUser();
        $this->activityLogger->log($report, $actor instanceof User ? $actor : null, 'assignment_changed', $previous, $assignedTo?->getEmail());
        $this->persistence->flush();

        return ApiResponse::success(['id' => $report->getId()], 200, 'Responsable mis à jour.');
    }
}
