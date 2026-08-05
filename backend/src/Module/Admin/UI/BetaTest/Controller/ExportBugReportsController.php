<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/export', name: 'api_admin_beta_reports_export', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ExportBugReportsController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = [
            'status' => trim((string) $request->query->get('status', '')),
            'severity' => trim((string) $request->query->get('severity', '')),
            'search' => trim((string) $request->query->get('search', '')),
            'campaignId' => $request->query->has('campaignId') && '' !== (string) $request->query->get('campaignId') ? $request->query->getInt('campaignId') : null,
            'assignedTo' => $request->query->has('assignedTo') && '' !== (string) $request->query->get('assignedTo') ? $request->query->getInt('assignedTo') : null,
        ];

        $response = new StreamedResponse(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }
            fputcsv($handle, ['ID', 'Titre', 'Utilisateur', 'Email', 'Gravité', 'État', 'Campagne', 'Responsable', 'Doublon de', 'Créé le']);
            foreach ($this->reports->findExportRows($filters) as $report) {
                fputcsv($handle, [
                    $report->getId(),
                    $report->getTitle(),
                    $report->getReporter()->getFullName(),
                    $report->getReporter()->getEmail(),
                    $report->getSeverity(),
                    $report->getStatus(),
                    $report->getCampaign()?->getName() ?? 'Général',
                    $report->getAssignedTo()?->getEmail() ?? '',
                    $report->getDuplicateOf()?->getId() ?? '',
                    $report->getCreatedAt()->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        });
        $this->attachments->applyHeaders($response, 'signalements-beta.csv', 'text/csv; charset=UTF-8');

        return $response;
    }
}
