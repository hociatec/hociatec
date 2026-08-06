<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\ChangeBugReportStatusHandler;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestPayloadMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/status', name: 'api_admin_beta_reports_status', methods: ['PATCH'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class UpdateBugReportStatusController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly ChangeBugReportStatusHandler $changeBugReportStatus,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $status = RequestPayloadMapper::string($payload, 'status');

        $admin = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $actor = $admin instanceof User ? $admin : null;
        try {
            $this->changeBugReportStatus->change($report, $status, $actor);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success([
            'id' => $report->getId(),
            'status' => $report->getStatus(),
        ], 200, 'État mis à jour.');
    }
}
