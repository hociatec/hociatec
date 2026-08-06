<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\BetaTest\Application\Port\BugReportActivityRepositoryPort;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/activity', name: 'api_admin_beta_reports_activity', methods: ['GET'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class ListBugReportActivitiesController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly BugReportActivityRepositoryPort $activities,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $items = array_map(static fn ($activity) => [
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
            ], $this->activities->findForReport($report));

        return ApiResponse::paginated(
            array_slice($items, $pagination->offset(), $pagination->perPage),
            $pagination->metadata(count($items)),
        );
    }
}
