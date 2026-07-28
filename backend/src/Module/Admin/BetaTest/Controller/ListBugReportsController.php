<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Http\BugReportResponseFormatter;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports', methods: ['GET'])] #[IsGranted('ROLE_ADMIN')]
final class ListBugReportsController extends AbstractController
{
    public function __construct(private readonly BugReportRepository $reports, private readonly BugReportResponseFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request, 12, 100);
        $filters = [
            'status' => trim((string) $request->query->get('status', '')),
            'severity' => trim((string) $request->query->get('severity', '')),
            'search' => trim((string) $request->query->get('search', '')),
            'campaignId' => $request->query->has('campaignId') && '' !== (string) $request->query->get('campaignId') ? $request->query->getInt('campaignId') : null,
            'assignedTo' => $request->query->has('assignedTo') && '' !== (string) $request->query->get('assignedTo') ? $request->query->getInt('assignedTo') : null,
        ];
        $total = $this->reports->countForAdmin($filters);

        return ApiResponse::paginated(
            array_map(fn ($report) => $this->formatter->format($report), $this->reports->findForAdmin($filters, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($total),
        );
    }
}
