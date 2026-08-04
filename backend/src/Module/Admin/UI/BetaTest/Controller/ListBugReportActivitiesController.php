<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\BetaTest\Infrastructure\Repository\BugReportActivityRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/activity', name: 'api_admin_beta_reports_activity', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListBugReportActivitiesController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly BugReportActivityRepository $activities,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        return ApiResponse::success([
            'items' => array_map(static fn ($activity) => [
                'id' => $activity->getId(),
                'action' => $activity->getAction(),
                'fromValue' => $activity->getFromValue(),
                'toValue' => $activity->getToValue(),
                'message' => $activity->getMessage(),
                'createdAt' => $activity->getCreatedAt()->format(DATE_ATOM),
                'actor' => null !== $activity->getActor() ? [
                    'id' => $activity->getActor()->getId(),
                    'name' => $activity->getActor()->getFullName(),
                    'email' => $activity->getActor()->getEmail(),
                ] : null,
            ], $this->activities->findForReport($report)),
        ]);
    }
}
