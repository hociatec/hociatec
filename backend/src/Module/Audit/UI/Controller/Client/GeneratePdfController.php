<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Module\Audit\Application\Workflow\CustomerAuditPortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GeneratePdfController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerAuditPortalService $portal,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    #[Route('/api/audits/{id}/pdf', name: 'api_audits_generate_pdf', methods: ['POST'])]
    public function detailed(int $id): Response
    {
        try {
            $pdf = $this->portal->renderDetailedPdfForUser($this->currentUser(), $id);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }
        if (null === $pdf) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->attachments->create($pdf['content'], $pdf['filename'], 'application/pdf');
    }

    #[Route('/api/audits/{id}/pdf-summary', name: 'api_audits_generate_pdf_summary', methods: ['POST'])]
    public function summary(int $id): Response
    {
        try {
            $pdf = $this->portal->renderSummaryPdfForUser($this->currentUser(), $id);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }
        if (null === $pdf) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->attachments->create($pdf['content'], $pdf['filename'], 'application/pdf');
    }
}
