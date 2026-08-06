<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Audit\Controller;

use App\Module\Audit\Application\Port\AuditPdfRenderer;
use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Module\Audit\Application\Workflow\AuditEventLogger;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AUDITS_MANAGER')]
class GeneratePdfController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepositoryPort $audits,
        private readonly AuditPdfRenderer $pdf,
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
        $actor = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
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
        $actor = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $this->events->log($audit, $actor, 'pdf_generated', 'Synthèse PDF');

        return $this->attachments->create($bin, sprintf('%s-synthese.pdf', $audit->getNumber()), 'application/pdf');
    }
}
