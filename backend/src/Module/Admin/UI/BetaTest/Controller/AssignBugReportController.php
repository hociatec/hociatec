<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Module\Admin\Application\BetaTest\Service\AssignBugReportHandler;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
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
        private readonly AssignBugReportHandler $assignBugReport,
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
