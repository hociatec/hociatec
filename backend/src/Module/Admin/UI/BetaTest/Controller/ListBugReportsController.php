<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\BetaTest\Application\Projection\BugReportResponseFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports', methods: ['GET'])] #[IsGranted('ROLE_BETA_MANAGER')]
final class ListBugReportsController extends AbstractController
{
    public function __construct(private readonly BugReportRepositoryPort $reports, private readonly BugReportResponseFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 12, 100);
        $filters = RequestQueryMapper::betaReportFilters($request);
        $total = $this->reports->countForAdmin($filters);

        return ApiResponse::paginated(
            array_map(fn ($report) => $this->formatter->format($report), $this->reports->findForAdmin($filters, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($total),
        );
    }
}
