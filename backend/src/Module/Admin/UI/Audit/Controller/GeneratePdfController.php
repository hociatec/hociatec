<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Audit\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\AttachmentResponseFactory;
use App\Module\Audit\Application\Service\AuditEventLogger;
use App\Module\Audit\Application\Service\AuditPdfService;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class GeneratePdfController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $audits,
        private readonly AuditPdfService $pdf,
        private readonly AuditEventLogger $events,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    #[Route('/api/admin/audits/{id}/pdf', name: 'api_admin_audits_generate_pdf', methods: ['POST'])]
    public function detailed(int $id): Response
    {
        $audit = $this->audits->find($id);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }
        try {
            $bin = $this->pdf->renderDetailed($audit);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }

        /** @var \App\Module\User\Domain\Entity\User|null $actor */
        $actor = $this->getUser();
        $this->events->log($audit, $actor, 'pdf_generated', 'Rapport détaillé');

        return $this->attachments->create($bin, sprintf('%s-rapport.pdf', $audit->getNumber()), 'application/pdf');
    }

    #[Route('/api/admin/audits/{id}/pdf-summary', name: 'api_admin_audits_generate_pdf_summary', methods: ['POST'])]
    public function summary(int $id): Response
    {
        $audit = $this->audits->find($id);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }
        try {
            $bin = $this->pdf->renderSummary($audit);
        } catch (\RuntimeException) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED);
        }

        /** @var \App\Module\User\Domain\Entity\User|null $actor */
        $actor = $this->getUser();
        $this->events->log($audit, $actor, 'pdf_generated', 'Synthèse PDF');

        return $this->attachments->create($bin, sprintf('%s-synthese.pdf', $audit->getNumber()), 'application/pdf');
    }
}
