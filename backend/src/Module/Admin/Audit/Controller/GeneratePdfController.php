<?php

declare(strict_types=1);

namespace App\Module\Admin\Audit\Controller;

use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\Audit\Service\AuditEventLogger;
use App\Module\Audit\Service\AuditPdfService;
use App\Shared\Http\ApiResponse;
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
        } catch (\Throwable $e) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED, [$e->getMessage()]);
        }

        /** @var \App\Module\User\Entity\User|null $actor */
        $actor = $this->getUser();
        $this->events->log($audit, $actor, 'pdf_generated', 'Rapport détaillé');

        $filename = sprintf('%s-rapport.pdf', $audit->getNumber());
        $response = new Response($bin);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
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
        } catch (\Throwable $e) {
            return ApiResponse::error('Génération PDF indisponible: installer dompdf/dompdf.', Response::HTTP_NOT_IMPLEMENTED, [$e->getMessage()]);
        }

        /** @var \App\Module\User\Entity\User|null $actor */
        $actor = $this->getUser();
        $this->events->log($audit, $actor, 'pdf_generated', 'Synthèse PDF');

        $filename = sprintf('%s-synthese.pdf', $audit->getNumber());
        $response = new Response($bin);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
