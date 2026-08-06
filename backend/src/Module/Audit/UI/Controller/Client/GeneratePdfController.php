<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Module\Audit\Application\Port\AuditPdfRenderer;
use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Module\Audit\Application\Workflow\AuditEventLogger;
use App\Module\Audit\Domain\Security\AuditAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GeneratePdfController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepositoryPort $audits,
        private readonly AuditPdfRenderer $pdf,
        private readonly AuditEventLogger $events,
        private readonly AttachmentResponseFactory $attachments,
        private readonly AuditAccessPolicy $accessPolicy,
    ) {
    }

    #[Route('/api/audits/{id}/pdf', name: 'api_audits_generate_pdf', methods: ['POST'])]
    public function detailed(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $audit = $this->audits->find($id);
        if (null === $audit || !$this->accessPolicy->canView($user, $audit)) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $bin = $this->pdf->renderDetailed($audit);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->events->log($audit, $user, 'pdf_generated', 'Rapport détaillé (client)');

        return $this->attachments->create($bin, sprintf('%s-rapport.pdf', $audit->getNumber()), 'application/pdf');
    }

    #[Route('/api/audits/{id}/pdf-summary', name: 'api_audits_generate_pdf_summary', methods: ['POST'])]
    public function summary(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $audit = $this->audits->find($id);
        if (null === $audit || !$this->accessPolicy->canView($user, $audit)) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $bin = $this->pdf->renderSummary($audit);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->events->log($audit, $user, 'pdf_generated', 'Synthèse PDF (client)');

        return $this->attachments->create($bin, sprintf('%s-synthese.pdf', $audit->getNumber()), 'application/pdf');
    }
}
